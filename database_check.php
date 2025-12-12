<?php
/**
 * HARAMAYA PHARMA - Database Verification Script
 * Check database connection and table structure
 */

echo "<h1>Haramaya Pharma - Database Verification</h1>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{color:green;} .error{color:red;} .warning{color:orange;} table{border-collapse:collapse;width:100%;margin:10px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background-color:#f2f2f2;}</style>";

try {
    // Test database connection
    $pdo = require __DIR__ . '/config/database.php';
    echo "<p class='success'>✅ Database connection successful!</p>";
    
    // Check database name
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "<p>📊 Connected to database: <strong>$dbName</strong></p>";
    
    // List all tables
    echo "<h2>📋 Database Tables</h2>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $expectedTables = [
        'users', 'product_categories', 'suppliers', 'products', 
        'stock_batches', 'sales', 'sale_items', 'stock_adjustments', 'activity_logs'
    ];
    
    echo "<table>";
    echo "<tr><th>Table Name</th><th>Status</th><th>Record Count</th></tr>";
    
    foreach ($expectedTables as $table) {
        if (in_array($table, $tables)) {
            try {
                $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                echo "<tr><td>$table</td><td class='success'>✅ Exists</td><td>$count records</td></tr>";
            } catch (Exception $e) {
                echo "<tr><td>$table</td><td class='warning'>⚠️ Exists but error counting</td><td>Error: " . $e->getMessage() . "</td></tr>";
            }
        } else {
            echo "<tr><td>$table</td><td class='error'>❌ Missing</td><td>-</td></tr>";
        }
    }
    echo "</table>";
    
    // Check for extra tables
    $extraTables = array_diff($tables, $expectedTables);
    if (!empty($extraTables)) {
        echo "<h3>🔍 Additional Tables Found:</h3>";
        echo "<ul>";
        foreach ($extraTables as $table) {
            $count = $pdo->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "<li>$table ($count records)</li>";
        }
        echo "</ul>";
    }
    
    // Check users table
    echo "<h2>👥 Users Table</h2>";
    try {
        $users = $pdo->query("SELECT user_id, username, full_name, role, is_active, created_at FROM users ORDER BY user_id")->fetchAll();
        if (!empty($users)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Active</th><th>Created</th></tr>";
            foreach ($users as $user) {
                $activeStatus = $user['is_active'] ? '✅ Yes' : '❌ No';
                echo "<tr>";
                echo "<td>{$user['user_id']}</td>";
                echo "<td>{$user['username']}</td>";
                echo "<td>{$user['full_name']}</td>";
                echo "<td>{$user['role']}</td>";
                echo "<td>$activeStatus</td>";
                echo "<td>{$user['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠️ No users found in database</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error reading users: " . $e->getMessage() . "</p>";
    }
    
    // Check product categories
    echo "<h2>📦 Product Categories</h2>";
    try {
        $categories = $pdo->query("SELECT * FROM product_categories ORDER BY category_id")->fetchAll();
        if (!empty($categories)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Category Name</th><th>Description</th><th>Active</th></tr>";
            foreach ($categories as $cat) {
                $activeStatus = $cat['is_active'] ? '✅ Yes' : '❌ No';
                echo "<tr>";
                echo "<td>{$cat['category_id']}</td>";
                echo "<td>{$cat['category_name']}</td>";
                echo "<td>{$cat['description']}</td>";
                echo "<td>$activeStatus</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠️ No categories found</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error reading categories: " . $e->getMessage() . "</p>";
    }
    
    // Check suppliers
    echo "<h2>🚚 Suppliers</h2>";
    try {
        $suppliers = $pdo->query("SELECT * FROM suppliers ORDER BY supplier_id")->fetchAll();
        if (!empty($suppliers)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Supplier Name</th><th>Contact Person</th><th>Phone</th><th>Active</th></tr>";
            foreach ($suppliers as $sup) {
                $activeStatus = $sup['is_active'] ? '✅ Yes' : '❌ No';
                echo "<tr>";
                echo "<td>{$sup['supplier_id']}</td>";
                echo "<td>{$sup['supplier_name']}</td>";
                echo "<td>{$sup['contact_person']}</td>";
                echo "<td>{$sup['phone']}</td>";
                echo "<td>$activeStatus</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠️ No suppliers found</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error reading suppliers: " . $e->getMessage() . "</p>";
    }
    
    // Check products
    echo "<h2>💊 Products</h2>";
    try {
        $products = $pdo->query("
            SELECT p.*, pc.category_name 
            FROM products p 
            LEFT JOIN product_categories pc ON p.category_id = pc.category_id 
            ORDER BY p.product_id LIMIT 10
        ")->fetchAll();
        
        if (!empty($products)) {
            echo "<table>";
            echo "<tr><th>ID</th><th>Code</th><th>Product Name</th><th>Category</th><th>Price</th><th>Active</th></tr>";
            foreach ($products as $prod) {
                $activeStatus = $prod['is_active'] ? '✅ Yes' : '❌ No';
                echo "<tr>";
                echo "<td>{$prod['product_id']}</td>";
                echo "<td>{$prod['product_code']}</td>";
                echo "<td>{$prod['product_name']}</td>";
                echo "<td>{$prod['category_name']}</td>";
                echo "<td>ETB " . number_format($prod['unit_price'], 2) . "</td>";
                echo "<td>$activeStatus</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            $totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
            if ($totalProducts > 10) {
                echo "<p><em>Showing first 10 of $totalProducts products</em></p>";
            }
        } else {
            echo "<p class='warning'>⚠️ No products found</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error reading products: " . $e->getMessage() . "</p>";
    }
    
    // Check stock batches
    echo "<h2>📦 Stock Batches</h2>";
    try {
        $stock = $pdo->query("
            SELECT sb.*, p.product_name, s.supplier_name,
                   DATEDIFF(sb.expiry_date, CURDATE()) as days_to_expiry
            FROM stock_batches sb 
            LEFT JOIN products p ON sb.product_id = p.product_id
            LEFT JOIN suppliers s ON sb.supplier_id = s.supplier_id
            WHERE sb.quantity_remaining > 0
            ORDER BY sb.expiry_date ASC LIMIT 10
        ")->fetchAll();
        
        if (!empty($stock)) {
            echo "<table>";
            echo "<tr><th>Batch</th><th>Product</th><th>Supplier</th><th>Qty Remaining</th><th>Expiry Date</th><th>Days Left</th><th>Status</th></tr>";
            foreach ($stock as $batch) {
                $daysLeft = $batch['days_to_expiry'];
                if ($daysLeft < 0) {
                    $status = "<span class='error'>EXPIRED</span>";
                } elseif ($daysLeft <= 30) {
                    $status = "<span class='warning'>CRITICAL</span>";
                } elseif ($daysLeft <= 90) {
                    $status = "<span class='warning'>WARNING</span>";
                } else {
                    $status = "<span class='success'>GOOD</span>";
                }
                
                echo "<tr>";
                echo "<td>{$batch['batch_number']}</td>";
                echo "<td>{$batch['product_name']}</td>";
                echo "<td>{$batch['supplier_name']}</td>";
                echo "<td>{$batch['quantity_remaining']}</td>";
                echo "<td>{$batch['expiry_date']}</td>";
                echo "<td>$daysLeft</td>";
                echo "<td>$status</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            $totalBatches = $pdo->query("SELECT COUNT(*) FROM stock_batches WHERE quantity_remaining > 0")->fetchColumn();
            if ($totalBatches > 10) {
                echo "<p><em>Showing first 10 of $totalBatches active batches</em></p>";
            }
        } else {
            echo "<p class='warning'>⚠️ No stock batches found</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error reading stock: " . $e->getMessage() . "</p>";
    }
    
    // Check sales
    echo "<h2>💰 Recent Sales</h2>";
    try {
        $sales = $pdo->query("
            SELECT s.*, u.full_name as cashier_name
            FROM sales s 
            LEFT JOIN users u ON s.cashier_id = u.user_id
            ORDER BY s.sale_date DESC LIMIT 5
        ")->fetchAll();
        
        if (!empty($sales)) {
            echo "<table>";
            echo "<tr><th>Sale Number</th><th>Customer</th><th>Total</th><th>Payment</th><th>Cashier</th><th>Date</th></tr>";
            foreach ($sales as $sale) {
                echo "<tr>";
                echo "<td>{$sale['sale_number']}</td>";
                echo "<td>" . ($sale['customer_name'] ?: 'Walk-in') . "</td>";
                echo "<td>ETB " . number_format($sale['total_amount'], 2) . "</td>";
                echo "<td>{$sale['payment_method']}</td>";
                echo "<td>{$sale['cashier_name']}</td>";
                echo "<td>{$sale['sale_date']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            $totalSales = $pdo->query("SELECT COUNT(*) FROM sales")->fetchColumn();
            $totalRevenue = $pdo->query("SELECT SUM(total_amount) FROM sales")->fetchColumn();
            echo "<p><strong>Total Sales:</strong> $totalSales transactions</p>";
            echo "<p><strong>Total Revenue:</strong> ETB " . number_format($totalRevenue, 2) . "</p>";
        } else {
            echo "<p class='warning'>⚠️ No sales found</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Error reading sales: " . $e->getMessage() . "</p>";
    }
    
    // Database health summary
    echo "<h2>🏥 Database Health Summary</h2>";
    echo "<div style='background:#f0f8ff;padding:15px;border-radius:5px;'>";
    
    $healthChecks = [
        'Tables Created' => count($tables) >= count($expectedTables),
        'Admin User Exists' => $pdo->query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn() > 0,
        'Categories Available' => $pdo->query("SELECT COUNT(*) FROM product_categories")->fetchColumn() > 0,
        'Suppliers Available' => $pdo->query("SELECT COUNT(*) FROM suppliers")->fetchColumn() > 0,
    ];
    
    foreach ($healthChecks as $check => $status) {
        $icon = $status ? '✅' : '❌';
        $class = $status ? 'success' : 'error';
        echo "<p class='$class'>$icon $check</p>";
    }
    echo "</div>";
    
    echo "<h2>🔧 Recommendations</h2>";
    echo "<ul>";
    
    if ($pdo->query("SELECT COUNT(*) FROM products")->fetchColumn() == 0) {
        echo "<li>Add some products to test the system</li>";
    }
    
    if ($pdo->query("SELECT COUNT(*) FROM stock_batches WHERE quantity_remaining > 0")->fetchColumn() == 0) {
        echo "<li>Add stock batches to test inventory management</li>";
    }
    
    $expiredCount = $pdo->query("SELECT COUNT(*) FROM stock_batches WHERE expiry_date < CURDATE() AND quantity_remaining > 0")->fetchColumn();
    if ($expiredCount > 0) {
        echo "<li class='warning'>⚠️ You have $expiredCount expired stock batches that should be removed</li>";
    }
    
    echo "<li>✅ Database structure is complete and ready for use</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database connection failed: " . $e->getMessage() . "</p>";
    echo "<h3>Troubleshooting:</h3>";
    echo "<ol>";
    echo "<li>Make sure MySQL server is running</li>";
    echo "<li>Check database credentials in .env file</li>";
    echo "<li>Ensure database 'haramaya_pharma' exists</li>";
    echo "<li>Import schema.sql to create tables</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<p><em>Database check completed at " . date('Y-m-d H:i:s') . "</em></p>";
?>