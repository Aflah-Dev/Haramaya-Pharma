<?php
/**
 * HARAMAYA PHARMA - Sales History
 */

$page_title = 'Sales History';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../templates/header.php';

// Get filter parameters
$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');
$payment_method = $_GET['payment_method'] ?? '';

// Build query
$query = "
    SELECT s.*, u.full_name as cashier_name,
           COUNT(si.sale_item_id) as item_count
    FROM sales s
    INNER JOIN users u ON s.cashier_id = u.user_id
    LEFT JOIN sale_items si ON s.sale_id = si.sale_id
    WHERE DATE(s.sale_date) BETWEEN ? AND ?
";

$params = [$date_from, $date_to];

if ($payment_method) {
    $query .= " AND s.payment_method = ?";
    $params[] = $payment_method;
}

$query .= " GROUP BY s.sale_id ORDER BY s.sale_date DESC LIMIT 100";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$sales = $stmt->fetchAll();

// Calculate summary
$total_sales = array_sum(array_column($sales, 'total_amount'));
$total_transactions = count($sales);
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Sales History</h2>
        <a href="pos.php" class="btn btn-primary">
            <i class="fas fa-cash-register"></i> New Sale
        </a>
    </div>
    
    <!-- Filters -->
    <form method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 1rem;">
        <div class="form-group">
            <label class="form-label">From Date</label>
            <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
        </div>
        <div class="form-group">
            <label class="form-label">To Date</label>
            <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
        </div>
        <div class="form-group">
            <label class="form-label">Payment Method</label>
            <select name="payment_method" class="form-control">
                <option value="">All</option>
                <option value="cash" <?php echo $payment_method === 'cash' ? 'selected' : ''; ?>>Cash</option>
                <option value="card" <?php echo $payment_method === 'card' ? 'selected' : ''; ?>>Card</option>
                <option value="mobile_money" <?php echo $payment_method === 'mobile_money' ? 'selected' : ''; ?>>Mobile Money</option>
            </select>
        </div>
        <div class="form-group" style="display: flex; align-items: flex-end;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
        </div>
    </form>
    
    <!-- Summary -->
    <div class="stats-grid" style="margin-bottom: 1.5rem;">
        <div class="stat-card">
            <div class="stat-label">Total Transactions</div>
            <div class="stat-value"><?php echo $total_transactions; ?></div>
        </div>
        <div class="stat-card success">
            <div class="stat-label">Total Sales</div>
            <div class="stat-value">ETB <?php echo number_format($total_sales, 2); ?></div>
        </div>
    </div>
    
    <!-- Sales Table -->
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Sale #</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Cashier</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sales)): ?>
                <tr><td colspan="8" style="text-align: center;">No sales found</td></tr>
                <?php else: ?>
                    <?php foreach ($sales as $sale): ?>
                    <tr>
                        <td><?php echo clean($sale['sale_number']); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($sale['sale_date'])); ?></td>
                        <td><?php echo clean($sale['customer_name'] ?: 'Walk-in'); ?></td>
                        <td><?php echo $sale['item_count']; ?></td>
                        <td><strong>ETB <?php echo number_format($sale['total_amount'], 2); ?></strong></td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo strtoupper(clean($sale['payment_method'])); ?>
                            </span>
                        </td>
                        <td><?php echo clean($sale['cashier_name']); ?></td>
                        <td>
                            <a href="receipt.php?sale_id=<?php echo $sale['sale_id']; ?>" 
                               class="btn btn-secondary" style="padding: 0.25rem 0.5rem;">
                                <i class="fas fa-receipt"></i> Receipt
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
