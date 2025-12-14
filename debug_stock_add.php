<?php
// Debug version of stock add - bypasses authentication temporarily
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Stock Add Form</h1>";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>POST Data Received:</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Try database connection
    try {
        $pdo = require __DIR__ . '/config/database.php';
        echo "<p style='color: green;'>✅ Database connection successful</p>";
        
        // Test query
        $count = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
        echo "<p>Products in database: $count</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
    }
    
    exit;
}

// Get products for dropdown
try {
    $pdo = require __DIR__ . '/config/database.php';
    $products = $pdo->query("SELECT * FROM products WHERE is_active = 1 ORDER BY product_name LIMIT 10")->fetchAll();
    $suppliers = $pdo->query("SELECT * FROM suppliers WHERE is_active = 1 ORDER BY supplier_name")->fetchAll();
} catch (Exception $e) {
    echo "<p style='color: red;'>Database connection failed: " . $e->getMessage() . "</p>";
    $products = [];
    $suppliers = [];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Stock Add</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select, textarea { width: 300px; padding: 8px; border: 1px solid #ccc; }
        button { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <h1>Debug Stock Add Form</h1>
    
    <form method="POST" onsubmit="console.log('Form submitted'); return true;">
        <div class="form-group">
            <label>Product:</label>
            <select name="product_id" required>
                <option value="">Select Product</option>
                <?php foreach ($products as $product): ?>
                <option value="<?php echo $product['product_id']; ?>">
                    <?php echo htmlspecialchars($product['product_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Batch Number:</label>
            <input type="text" name="batch_number" required placeholder="e.g., BATCH-2024-001">
        </div>
        
        <div class="form-group">
            <label>Supplier:</label>
            <select name="supplier_id">
                <option value="">Select Supplier</option>
                <?php foreach ($suppliers as $supplier): ?>
                <option value="<?php echo $supplier['supplier_id']; ?>">
                    <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label>Quantity:</label>
            <input type="number" name="quantity" required min="1" value="100">
        </div>
        
        <div class="form-group">
            <label>Unit Cost (ETB):</label>
            <input type="number" step="0.01" name="unit_cost" required min="0.01" value="10.00">
        </div>
        
        <div class="form-group">
            <label>Expiry Date:</label>
            <input type="date" name="expiry_date" required value="<?php echo date('Y-m-d', strtotime('+1 year')); ?>">
        </div>
        
        <div class="form-group">
            <button type="submit">💾 Save Stock (Debug)</button>
        </div>
    </form>
    
    <script>
        console.log('Debug form loaded');
        document.querySelector('form').addEventListener('submit', function(e) {
            console.log('Form submit event triggered');
        });
    </script>
</body>
</html>
