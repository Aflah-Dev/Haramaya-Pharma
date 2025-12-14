<?php


// HARAMAYA PHARMA - Main Dashboard
 // Overview of key metrics and alerts

$page_title = 'Dashboard';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/alerts.php';
require_once __DIR__ . '/../../templates/header.php';

// Fetch dashboard statistics
try {
    // Total products
    $total_products = $pdo->query("SELECT COUNT(DISTINCT product_id) FROM products WHERE is_active = 1")->fetchColumn();
    
    // Total stock value
    $stock_value_query = "SELECT SUM(quantity_remaining * unit_cost) as total_value FROM stock_batches WHERE quantity_remaining > 0";
    $stock_value = $pdo->query($stock_value_query)->fetchColumn() ?? 0;
    
    // Today's sales
    $today_sales_query = "SELECT COALESCE(SUM(total_amount), 0) as today_sales FROM sales WHERE DATE(sale_date) = CURDATE()";
    $today_sales = $pdo->query($today_sales_query)->fetchColumn();
    
    // Expiring items (next 30 days)
    $expiring_query = "SELECT COUNT(*) FROM stock_batches WHERE DATEDIFF(expiry_date, CURDATE()) BETWEEN 0 AND 30 AND quantity_remaining > 0";
    $expiring_count = $pdo->query($expiring_query)->fetchColumn();
    
    // Low stock items
    try {
        $low_stock_count = count(get_low_stock_alerts($pdo));
    } catch (Exception $e) {
        error_log("Dashboard low stock error: " . $e->getMessage());
        $low_stock_count = 0;
    }
    
    // Recent sales
    $recent_sales_query = "
        SELECT s.sale_number, s.sale_date, s.total_amount, s.customer_name, u.full_name as cashier
        FROM sales s
        INNER JOIN users u ON s.cashier_id = u.user_id
        ORDER BY s.sale_date DESC
        LIMIT 5
    ";
    $recent_sales = $pdo->query($recent_sales_query)->fetchAll();
    
    // Critical expiry alerts
    $critical_expiry_query = "
        SELECT p.product_name, sb.batch_number, sb.expiry_date, sb.quantity_remaining,
               DATEDIFF(sb.expiry_date, CURDATE()) as days_left
        FROM stock_batches sb
        INNER JOIN products p ON sb.product_id = p.product_id
        WHERE DATEDIFF(sb.expiry_date, CURDATE()) BETWEEN 0 AND 30
        AND sb.quantity_remaining > 0
        ORDER BY sb.expiry_date ASC
        LIMIT 5
    ";
    $critical_items = $pdo->query($critical_expiry_query)->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard error: " . $e->getMessage());
}
?>



<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card success">
        <div class="stat-label"><i class="fas fa-capsules"></i> Total Products</div>
        <div class="stat-value"><?php echo number_format($total_products); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-dollar-sign"></i> Stock Value</div>
        <div class="stat-value">ETB <?php echo number_format($stock_value, 2); ?></div>
    </div>
    
    <div class="stat-card success">
        <div class="stat-label"><i class="fas fa-chart-line"></i> Today's Sales</div>
        <div class="stat-value">ETB <?php echo number_format($today_sales, 2); ?></div>
    </div>
    
    <div class="stat-card warning">
        <div class="stat-label"><i class="fas fa-exclamation-triangle"></i> Expiring Soon</div>
        <div class="stat-value"><?php echo $expiring_count; ?></div>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Recent Sales -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Sales</h2>
            <a href="../sales/history.php" class="btn btn-secondary">View All</a>
        </div>
        <div class="table-container">
            <table class="data-table mobile-stack-table">
                <thead>
                    <tr>
                        <th>Sale #</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_sales)): ?>
                        <tr><td colspan="4" style="text-align: center;">No sales yet</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent_sales as $sale): ?>
                        <tr>
                            <td data-label="Sale #"><?php echo clean($sale['sale_number']); ?></td>
                            <td data-label="Customer"><?php echo clean($sale['customer_name'] ?? 'Walk-in'); ?></td>
                            <td data-label="Amount">ETB <?php echo number_format($sale['total_amount'], 2); ?></td>
                            <td data-label="Date"><?php echo date('M d, H:i', strtotime($sale['sale_date'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Critical Expiry Alerts -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Critical Expiry Alerts</h2>
            <a href="../stock/expiry-alerts.php" class="btn btn-danger">View All</a>
        </div>
        <div class="table-container">
            <table class="data-table mobile-stack-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Batch</th>
                        <th>Days Left</th>
                        <th>Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($critical_items)): ?>
                        <tr><td colspan="4" style="text-align: center;">No critical items</td></tr>
                    <?php else: ?>
                        <?php foreach ($critical_items as $item): ?>
                        <tr>
                            <td data-label="Product"><?php echo clean($item['product_name']); ?></td>
                            <td data-label="Batch"><?php echo clean($item['batch_number']); ?></td>
                            <td data-label="Days Left">
                                <span class="badge <?php echo $item['days_left'] < 15 ? 'badge-danger' : 'badge-warning'; ?>">
                                    <?php echo $item['days_left']; ?> days
                                </span>
                            </td>
                            <td data-label="Qty"><?php echo $item['quantity_remaining']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
