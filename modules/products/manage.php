<?php


//HARAMAYA PHARMA - Product Management


// Error reporting disabled for production

$page_title = 'Manage Products';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../templates/header.php';
require_role(['admin', 'pharmacist']);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    
    $action = $_POST['action'] ?? '';
    
    // Add product
    if ($action === 'add') {
        $product_code = sanitize_input($_POST['product_code']);
        $product_name = sanitize_input($_POST['product_name']);
        $generic_name = sanitize_input($_POST['generic_name']);
        $category_id = (int)$_POST['category_id'];
        $dosage_form = sanitize_input($_POST['dosage_form']);
        $strength = sanitize_input($_POST['strength']);
        $unit_price = (float)$_POST['unit_price'];
        $reorder_level = (int)$_POST['reorder_level'];
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO products (product_code, product_name, generic_name, category_id, 
                                     dosage_form, strength, unit_price, reorder_level)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $product_code, $product_name, $generic_name, $category_id,
                $dosage_form, $strength, $unit_price, $reorder_level
            ]);
            
            log_security_event($pdo, $current_user['user_id'], 'PRODUCT_ADDED', "Product: $product_name");
            echo "<script>alert('Product added successfully!'); window.location='manage.php';</script>";
        } catch (PDOException $e) {
            echo "<script>alert('Error: Product code already exists');</script>";
        }
    }
    
    // Update product
    if ($action === 'update') {
        $product_id = (int)$_POST['product_id'];
        $unit_price = (float)$_POST['unit_price'];
        $reorder_level = (int)$_POST['reorder_level'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $pdo->prepare("
            UPDATE products 
            SET unit_price = ?, reorder_level = ?, is_active = ?
            WHERE product_id = ?
        ");
        $stmt->execute([$unit_price, $reorder_level, $is_active, $product_id]);
        
        log_security_event($pdo, $current_user['user_id'], 'PRODUCT_UPDATED', "Product ID: $product_id");
        echo "<script>alert('Product updated successfully!'); window.location='manage.php';</script>";
        exit;
    }
}

// Get categories
$categories = $pdo->query("SELECT * FROM product_categories ORDER BY category_name")->fetchAll();

// Get products
$products = $pdo->query("
    SELECT p.*, pc.category_name,
           COALESCE(SUM(sb.quantity_remaining), 0) as total_stock
    FROM products p
    LEFT JOIN product_categories pc ON p.category_id = pc.category_id
    LEFT JOIN stock_batches sb ON p.product_id = sb.product_id 
        AND sb.quantity_remaining > 0 
        AND sb.expiry_date >= CURDATE()
    GROUP BY p.product_id
    ORDER BY p.product_name
")->fetchAll();
?>

<!-- Add Product Form -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Add New Product</h2>
    </div>
    <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="add">
        
        <div class="form-group">
            <label class="form-label">Product Code *</label>
            <input type="text" name="product_code" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">Product Name *</label>
            <input type="text" name="product_name" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">Generic Name</label>
            <input type="text" name="generic_name" class="form-control">
        </div>
        
        <div class="form-group">
            <label class="form-label">Category</label>
            <select name="category_id" class="form-control">
                <option value="">Select Category</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['category_id']; ?>">
                    <?php echo clean($cat['category_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">Dosage Form</label>
            <input type="text" name="dosage_form" class="form-control" placeholder="e.g., Tablet, Capsule">
        </div>
        
        <div class="form-group">
            <label class="form-label">Strength</label>
            <input type="text" name="strength" class="form-control" placeholder="e.g., 500mg">
        </div>
        
        <div class="form-group">
            <label class="form-label">Unit Price (ETB) *</label>
            <input type="number" step="0.01" name="unit_price" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">Reorder Level</label>
            <input type="number" name="reorder_level" class="form-control" value="10">
        </div>
        
        <div class="form-group" style="display: flex; align-items: flex-end;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Product
            </button>
        </div>
    </form>
</div>

<!-- Products List -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Products List</h2>
    </div>
    
    <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" id="productSearch" class="form-control" placeholder="Search products...">
    </div>
    
    <div class="table-container">
        <table class="data-table" id="productTable">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Reorder</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo clean($product['product_code']); ?></td>
                    <td>
                        <strong><?php echo clean($product['product_name']); ?></strong><br>
                        <small><?php echo clean($product['generic_name']); ?> 
                               <?php echo clean($product['strength']); ?></small>
                    </td>
                    <td><?php echo clean($product['category_name'] ?: 'N/A'); ?></td>
                <form method="POST" style="display: contents;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                    
                    <td>
                        <input type="number" step="0.01" name="unit_price" 
                               value="<?php echo $product['unit_price']; ?>" 
                               style="width: 100px;" class="form-control">
                    </td>
                    <td>
                        <span class="badge <?php echo $product['total_stock'] > $product['reorder_level'] ? 'badge-success' : 'badge-warning'; ?>">
                            <?php echo $product['total_stock']; ?>
                        </span>
                    </td>
                    <td>
                        <input type="number" name="reorder_level" 
                               value="<?php echo $product['reorder_level']; ?>" 
                               style="width: 80px;" class="form-control">
                    </td>
                    <td>
                        <input type="checkbox" name="is_active" <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                    </td>
                    <td>
                        <button type="submit" class="btn btn-primary" style="padding: 0.25rem 0.5rem;">
                            <i class="fas fa-save"></i> Save
                        </button>
                    </td>
                </form>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('productSearch').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#productTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
