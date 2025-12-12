<?php
/**
 * HARAMAYA PHARMA - Point of Sale (POS)
 * Complete POS system with FEFO batch allocation
 */

$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';

secure_session_start();
require_login();

// Get current user
$current_user = get_logged_user();

// Initialize cart
if (!isset($_SESSION['pos_cart'])) {
    $_SESSION['pos_cart'] = [];
}

// Handle actions BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    
    $action = $_POST['action'] ?? '';
    
    // Add to cart
    if ($action === 'add_to_cart') {
        $product_id = (int)$_POST['product_id'];
        $quantity = (int)$_POST['quantity'];
        
        if ($product_id && $quantity > 0) {
            // Check if product exists in cart
            $found = false;
            foreach ($_SESSION['pos_cart'] as &$item) {
                if ($item['product_id'] === $product_id) {
                    $item['quantity'] += $quantity;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $_SESSION['pos_cart'][] = [
                    'product_id' => $product_id,
                    'quantity' => $quantity
                ];
            }
        }
        header('Location: pos.php');
        exit;
    }
    
    // Remove from cart
    if ($action === 'remove_from_cart') {
        $product_id = (int)$_POST['product_id'];
        $_SESSION['pos_cart'] = array_values(array_filter($_SESSION['pos_cart'], function($item) use ($product_id) {
            return $item['product_id'] !== $product_id;
        }));
        header('Location: pos.php');
        exit;
    }
    
    // Clear cart
    if ($action === 'clear_cart') {
        $_SESSION['pos_cart'] = [];
        header('Location: pos.php');
        exit;
    }
    
    // Process checkout
    if ($action === 'checkout') {
        $customer_name = sanitize_input($_POST['customer_name'] ?? '');
        $customer_phone = sanitize_input($_POST['customer_phone'] ?? '');
        $payment_method = $_POST['payment_method'] ?? 'cash';
        $amount_paid = (float)($_POST['amount_paid'] ?? 0);
        
        if (empty($_SESSION['pos_cart'])) {
            echo "<script>alert('Cart is empty!'); window.location='pos.php';</script>";
            exit;
        }
        
        try {
            $pdo->beginTransaction();
            
            // Generate sale number
            $sale_number = 'SALE-' . date('Ymd-His');
            
            $subtotal = 0;
            $tax = 0;
            
            // Calculate totals first
            foreach ($_SESSION['pos_cart'] as $cart_item) {
                $product_id = $cart_item['product_id'];
                $quantity = $cart_item['quantity'];
                
                // Get product price
                $stmt = $pdo->prepare("SELECT unit_price FROM products WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                
                $line_subtotal = $product['unit_price'] * $quantity;
                $line_tax = $line_subtotal * 0.15; // 15% tax
                
                $subtotal += $line_subtotal;
                $tax += $line_tax;
            }
            
            $total = $subtotal + $tax;
            $change = $amount_paid - $total;
            
            // Insert sale
            $stmt = $pdo->prepare("
                INSERT INTO sales (sale_number, cashier_id, customer_name, customer_phone, 
                                   subtotal, tax, total_amount, amount_paid, change_amount, payment_method)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $sale_number,
                $current_user['user_id'],
                $customer_name,
                $customer_phone,
                $subtotal,
                $tax,
                $total,
                $amount_paid,
                $change,
                $payment_method
            ]);
            
            $sale_id = $pdo->lastInsertId();
            
            // Process each cart item with FEFO
            foreach ($_SESSION['pos_cart'] as $cart_item) {
                $product_id = $cart_item['product_id'];
                $quantity_needed = $cart_item['quantity'];
                
                // Get product price
                $stmt = $pdo->prepare("SELECT unit_price FROM products WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch();
                
                // Get batches with FEFO ordering
                $stmt = $pdo->prepare("
                    SELECT * FROM stock_batches 
                    WHERE product_id = ? 
                    AND quantity_remaining > 0 
                    AND expiry_date >= CURDATE()
                    ORDER BY expiry_date ASC
                    FOR UPDATE
                ");
                $stmt->execute([$product_id]);
                $batches = $stmt->fetchAll();
                
                if (empty($batches)) {
                    throw new Exception("Insufficient stock for product ID: $product_id");
                }
                
                // Allocate from batches (FEFO)
                $remaining = $quantity_needed;
                foreach ($batches as $batch) {
                    if ($remaining <= 0) break;
                    
                    $take = min($remaining, $batch['quantity_remaining']);
                    
                    // Update batch quantity
                    $update = $pdo->prepare("
                        UPDATE stock_batches 
                        SET quantity_remaining = quantity_remaining - ? 
                        WHERE batch_id = ?
                    ");
                    $update->execute([$take, $batch['batch_id']]);
                    
                    // Insert sale item
                    $insert = $pdo->prepare("
                        INSERT INTO sale_items (sale_id, product_id, batch_id, quantity, unit_price, subtotal)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $insert->execute([
                        $sale_id,
                        $product_id,
                        $batch['batch_id'],
                        $take,
                        $product['unit_price'],
                        $product['unit_price'] * $take
                    ]);
                    
                    $remaining -= $take;
                }
                
                if ($remaining > 0) {
                    throw new Exception("Not enough stock to fulfill order");
                }
            }
            
            // Log activity
            log_security_event($pdo, $current_user['user_id'], 'SALE_COMPLETED', "Sale: $sale_number, Total: $total");
            
            $pdo->commit();
            
            // Clear cart
            $_SESSION['pos_cart'] = [];
            
            // Redirect to receipt
            header("Location: receipt.php?sale_id=$sale_id");
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<script>alert('Checkout failed: " . addslashes($e->getMessage()) . "'); window.location='pos.php';</script>";
            exit;
        }
    }
}

// Include header template AFTER processing POST requests
$page_title = 'Point of Sale';
require_once __DIR__ . '/../../templates/header.php';

// Get cart details
$cart_details = [];
$cart_total = 0;
foreach ($_SESSION['pos_cart'] as $cart_item) {
    $stmt = $pdo->prepare("
        SELECT p.*, 
               COALESCE(SUM(sb.quantity_remaining), 0) as available_stock
        FROM products p
        LEFT JOIN stock_batches sb ON p.product_id = sb.product_id 
            AND sb.quantity_remaining > 0 
            AND sb.expiry_date >= CURDATE()
        WHERE p.product_id = ?
        GROUP BY p.product_id
    ");
    $stmt->execute([$cart_item['product_id']]);
    $product = $stmt->fetch();
    
    if ($product) {
        $line_total = $product['unit_price'] * $cart_item['quantity'];
        $cart_total += $line_total;
        
        $cart_details[] = [
            'product' => $product,
            'quantity' => $cart_item['quantity'],
            'line_total' => $line_total
        ];
    }
}

$cart_tax = $cart_total * 0.15;
$cart_grand_total = $cart_total + $cart_tax;

// Get products for selection
$products = $pdo->query("
    SELECT p.*, 
           COALESCE(SUM(sb.quantity_remaining), 0) as available_stock
    FROM products p
    LEFT JOIN stock_batches sb ON p.product_id = sb.product_id 
        AND sb.quantity_remaining > 0 
        AND sb.expiry_date >= CURDATE()
    WHERE p.is_active = 1
    GROUP BY p.product_id
    ORDER BY p.product_name
    LIMIT 100
")->fetchAll();
?>

<div style="display: grid; grid-template-columns: 1.5fr 1fr; gap: 1.5rem;">
    <!-- Product Selection -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Select Products</h2>
        </div>
        
        <div class="search-box">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="productSearch" class="form-control" placeholder="Search products...">
        </div>
        
        <div class="table-container">
            <table class="data-table" id="productTable">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td>
                            <strong><?php echo clean($product['product_name']); ?></strong><br>
                            <small><?php echo clean($product['generic_name']); ?></small>
                        </td>
                        <td>ETB <?php echo number_format($product['unit_price'], 2); ?></td>
                        <td>
                            <span class="badge <?php echo $product['available_stock'] > 0 ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $product['available_stock']; ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['available_stock']; ?>" 
                                       style="width: 60px; display: inline;" class="form-control" 
                                       <?php echo $product['available_stock'] <= 0 ? 'disabled' : ''; ?>>
                                <button type="submit" class="btn btn-primary" <?php echo $product['available_stock'] <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-plus"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Cart & Checkout -->
    <div>
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">Cart</h2>
                <form method="POST" style="display: inline;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="clear_cart">
                    <button type="submit" class="btn btn-secondary">Clear</button>
                </form>
            </div>
            
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($cart_details)): ?>
                        <tr><td colspan="4" style="text-align: center;">Cart is empty</td></tr>
                        <?php else: ?>
                            <?php foreach ($cart_details as $item): ?>
                            <tr>
                                <td><?php echo clean($item['product']['product_name']); ?></td>
                                <td><?php echo $item['quantity']; ?></td>
                                <td>ETB <?php echo number_format($item['line_total'], 2); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="action" value="remove_from_cart">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product']['product_id']; ?>">
                                        <button type="submit" class="btn btn-danger" style="padding: 0.25rem 0.5rem;">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Cart Summary -->
            <div style="padding: 1rem; border-top: 2px solid var(--border-color); background: #f8f9fa;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 1rem;">
                    <span>Subtotal:</span>
                    <strong>ETB <?php echo number_format($cart_total, 2); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 1rem;">
                    <span>Tax (15%):</span>
                    <strong>ETB <?php echo number_format($cart_tax, 2); ?></strong>
                </div>
                <div style="display: flex; justify-content: space-between; font-size: 1.5rem; color: #28a745; border-top: 2px solid #28a745; padding-top: 0.5rem; margin-top: 0.5rem;">
                    <span><strong>TOTAL:</strong></span>
                    <strong>ETB <?php echo number_format($cart_grand_total, 2); ?></strong>
                </div>
                <div style="text-align: center; margin-top: 0.5rem; font-size: 0.9rem; color: #666;">
                    Items: <?php echo count($cart_details); ?> | Qty: <?php echo array_sum(array_column($cart_details, 'quantity')); ?>
                </div>
            </div>
        </div>
        
        <!-- Checkout Form -->
        <?php if (!empty($cart_details)): ?>
        <div class="card" style="border: 2px solid #28a745; box-shadow: 0 4px 12px rgba(40, 167, 69, 0.2);">
            <div class="card-header" style="background: #28a745; color: white; text-align: center;">
                <h3 style="margin: 0; font-size: 1.3rem;">💳 CHECKOUT</h3>
                <div style="font-size: 1.1rem; margin-top: 0.5rem;">
                    Total: <strong>ETB <?php echo number_format($cart_grand_total, 2); ?></strong>
                </div>
            </div>
            
            <form method="POST" id="checkoutForm">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="checkout">
                
                <div style="padding: 1rem;">
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i> Customer Name (Optional)
                        </label>
                        <input type="text" name="customer_name" class="form-control" placeholder="Enter customer name">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-phone"></i> Customer Phone (Optional)
                        </label>
                        <input type="text" name="customer_phone" class="form-control" placeholder="Enter phone number">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-credit-card"></i> Payment Method
                        </label>
                        <select name="payment_method" class="form-control" required id="paymentMethod">
                            <option value="cash">💵 Cash</option>
                            <option value="card">💳 Card</option>
                            <option value="mobile_money">📱 Mobile Money</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-money-bill-wave"></i> Amount Paid (ETB)
                        </label>
                        <input type="number" step="any" name="amount_paid" class="form-control" 
                               value="<?php echo $cart_grand_total; ?>" required id="amountPaid"
                               style="font-size: 1.2rem; font-weight: bold; text-align: center;">
                    </div>
                    
                    <div id="changeDisplay" style="background: #e9ecef; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem; text-align: center;">
                        <strong>Change: ETB <span id="changeAmount">0.00</span></strong>
                    </div>
                    
                    <button type="submit" class="btn btn-success" style="width: 100%; padding: 1.2rem; font-size: 1.2rem; font-weight: bold;">
                        <i class="fas fa-check-circle"></i> COMPLETE SALE - ETB <?php echo number_format($cart_grand_total, 2); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Product search
document.getElementById('productSearch').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#productTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Calculate change in real-time
<?php if (!empty($cart_details)): ?>
const totalAmount = <?php echo $cart_grand_total; ?>;
const amountPaidInput = document.getElementById('amountPaid');
const changeAmountSpan = document.getElementById('changeAmount');
const changeDisplay = document.getElementById('changeDisplay');

function calculateChange() {
    const amountPaid = parseFloat(amountPaidInput.value) || 0;
    const change = amountPaid - totalAmount;
    
    changeAmountSpan.textContent = change.toFixed(2);
    
    if (change < 0) {
        changeDisplay.style.background = '#f8d7da';
        changeDisplay.style.color = '#721c24';
        changeAmountSpan.parentElement.innerHTML = '<strong>Insufficient Payment: ETB ' + Math.abs(change).toFixed(2) + '</strong>';
    } else {
        changeDisplay.style.background = '#d4edda';
        changeDisplay.style.color = '#155724';
        changeAmountSpan.parentElement.innerHTML = '<strong>Change: ETB ' + change.toFixed(2) + '</strong>';
    }
}

amountPaidInput.addEventListener('input', calculateChange);
amountPaidInput.addEventListener('keyup', calculateChange);

// Initialize change calculation
calculateChange();

// Checkout form validation
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    const amountPaid = parseFloat(amountPaidInput.value) || 0;
    if (amountPaid < totalAmount) {
        e.preventDefault();
        alert('Amount paid is insufficient! Please enter at least ETB ' + totalAmount.toFixed(2));
        amountPaidInput.focus();
        return false;
    }
    
    // Confirm checkout
    const customerName = document.querySelector('input[name="customer_name"]').value;
    const paymentMethod = document.querySelector('select[name="payment_method"]').value;
    
    const confirmMsg = `Complete this sale?\n\nTotal: ETB ${totalAmount.toFixed(2)}\nPaid: ETB ${amountPaid.toFixed(2)}\nChange: ETB ${(amountPaid - totalAmount).toFixed(2)}\nPayment: ${paymentMethod.toUpperCase()}${customerName ? '\nCustomer: ' + customerName : ''}`;
    
    if (!confirm(confirmMsg)) {
        e.preventDefault();
        return false;
    }
});

// Quick amount buttons
const quickAmounts = [50, 100, 200, 500, 1000];
const amountPaidGroup = amountPaidInput.parentElement;
const quickButtonsDiv = document.createElement('div');
quickButtonsDiv.style.marginTop = '0.5rem';
quickButtonsDiv.innerHTML = '<small style="color: #666;">Quick amounts:</small><br>';

quickAmounts.forEach(amount => {
    if (amount >= totalAmount) {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = amount;
        btn.style.cssText = 'margin: 2px; padding: 4px 8px; font-size: 0.8rem; border: 1px solid #ccc; background: #f8f9fa; cursor: pointer;';
        btn.onclick = () => {
            amountPaidInput.value = amount;
            calculateChange();
        };
        quickButtonsDiv.appendChild(btn);
    }
});

amountPaidGroup.appendChild(quickButtonsDiv);
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
