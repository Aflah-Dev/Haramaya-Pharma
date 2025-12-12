<?php
/**
 * HARAMAYA PHARMA - Add Stock (GRN - Goods Received Note)
 */

$page_title = 'Add Stock';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../templates/header.php';
require_role(['admin', 'pharmacist', 'inventory']);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    
    $product_id = (int)$_POST['product_id'];
    $batch_number = sanitize_input($_POST['batch_number']);
    $supplier_id = (int)$_POST['supplier_id'];
    $quantity = (int)$_POST['quantity'];
    $unit_cost = (float)$_POST['unit_cost'];
    $expiry_date = $_POST['expiry_date'];
    $manufacture_date = $_POST['manufacture_date'];
    $received_date = $_POST['received_date'] ?: date('Y-m-d');
    $notes = sanitize_input($_POST['notes']);
    
    if ($product_id && $batch_number && $quantity > 0 && $unit_cost > 0 && $expiry_date) {
        // Check if expiry date is in the future
        if (strtotime($expiry_date) < time()) {
            echo "<script>alert('Cannot add expired stock!');</script>";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO stock_batches 
                    (product_id, batch_number, supplier_id, quantity_received, quantity_remaining, 
                     unit_cost, expiry_date, manufacture_date, received_date, received_by, notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $product_id, $batch_number, $supplier_id, $quantity, $quantity,
                    $unit_cost, $expiry_date, $manufacture_date, $received_date, 
                    $current_user['user_id'], $notes
                ]);
                
                log_security_event($pdo, $current_user['user_id'], 'STOCK_ADDED', 
                    "Product ID: $product_id, Batch: $batch_number, Qty: $quantity");
                
                echo "<script>alert('Stock added successfully!'); window.location='add.php';</script>";
            } catch (PDOException $e) {
                echo "<script>alert('Error: Batch already exists or invalid data');</script>";
            }
        }
    }
}

// Get products
$products = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY product_name")->fetchAll();

// Get suppliers
$suppliers = $pdo->query("SELECT * FROM suppliers WHERE is_active = 1 ORDER BY supplier_name")->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Add New Stock Batch (GRN)</h2>
        <a href="view.php" class="btn btn-secondary">
            <i class="fas fa-list"></i> View Stock
        </a>
    </div>
    
    <form method="POST">
        <?php echo csrf_field(); ?>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            <div class="form-group">
                <label class="form-label">Product *</label>
                <select name="product_id" class="form-control" required>
                    <option value="">Select Product</option>
                    <?php foreach ($products as $product): ?>
                    <option value="<?php echo $product['product_id']; ?>">
                        <?php echo clean($product['product_name']); ?> 
                        (<?php echo clean($product['product_code']); ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Batch Number *</label>
                <input type="text" name="batch_number" class="form-control" required 
                       placeholder="e.g., BATCH-2024-001">
            </div>
            
            <div class="form-group">
                <label class="form-label">Supplier</label>
                <select name="supplier_id" class="form-control">
                    <option value="">Select Supplier</option>
                    <?php foreach ($suppliers as $supplier): ?>
                    <option value="<?php echo $supplier['supplier_id']; ?>">
                        <?php echo clean($supplier['supplier_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Quantity Received *</label>
                <input type="number" name="quantity" class="form-control" required min="1">
            </div>
            
            <div class="form-group">
                <label class="form-label">Unit Cost (ETB) *</label>
                <input type="number" step="0.01" name="unit_cost" class="form-control" required min="0.01">
            </div>
            
            <div class="form-group">
                <label class="form-label">Expiry Date *</label>
                <input type="date" name="expiry_date" class="form-control" required 
                       min="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Manufacture Date</label>
                <input type="date" name="manufacture_date" class="form-control" 
                       max="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Received Date</label>
                <input type="date" name="received_date" class="form-control" 
                       value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            
            <div class="form-group" style="grid-column: 1 / -1;">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="3" 
                          placeholder="Additional notes about this batch..."></textarea>
            </div>
        </div>
        
        <div style="margin-top: 1.5rem;">
            <button type="submit" class="btn btn-success" style="padding: 0.75rem 2rem;">
                <i class="fas fa-check-circle"></i> Add Stock Batch
            </button>
            <button type="reset" class="btn btn-secondary" style="padding: 0.75rem 2rem;">
                <i class="fas fa-redo"></i> Reset
            </button>
        </div>
    </form>
</div>

<!-- Recent Additions -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Recent Stock Additions</h2>
    </div>
    
    <?php
    $recent = $pdo->query("
        SELECT sb.*, p.product_name, p.product_code, s.supplier_name, u.full_name as received_by_name
        FROM stock_batches sb
        INNER JOIN products p ON sb.product_id = p.product_id
        LEFT JOIN suppliers s ON sb.supplier_id = s.supplier_id
        LEFT JOIN users u ON sb.received_by = u.user_id
        ORDER BY sb.created_at DESC
        LIMIT 10
    ")->fetchAll();
    ?>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Batch</th>
                    <th>Quantity</th>
                    <th>Cost</th>
                    <th>Expiry</th>
                    <th>Supplier</th>
                    <th>Received By</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent as $item): ?>
                <tr>
                    <td>
                        <strong><?php echo clean($item['product_name']); ?></strong><br>
                        <small><?php echo clean($item['product_code']); ?></small>
                    </td>
                    <td><?php echo clean($item['batch_number']); ?></td>
                    <td><?php echo $item['quantity_received']; ?></td>
                    <td>ETB <?php echo number_format($item['unit_cost'], 2); ?></td>
                    <td><?php echo date('M d, Y', strtotime($item['expiry_date'])); ?></td>
                    <td><?php echo clean($item['supplier_name'] ?: 'N/A'); ?></td>
                    <td><?php echo clean($item['received_by_name']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($item['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
