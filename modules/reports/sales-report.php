<?php
/**
 * HARAMAYA PHARMA - Sales Report
 */

$page_title = 'Sales Report';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../templates/header.php';
require_role(['admin', 'pharmacist']);

// Get date range (default: last 30 days)
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Fetch sales summary
$stmt = $pdo->prepare("
    SELECT 
        DATE(s.sale_date) as sale_day,
        COUNT(s.sale_id) as total_transactions,
        SUM(s.total_amount) as daily_revenue,
        SUM(si.quantity) as items_sold
    FROM sales s
    LEFT JOIN sale_items si ON s.sale_id = si.sale_id
    WHERE DATE(s.sale_date) BETWEEN ? AND ?
    GROUP BY DATE(s.sale_date)
    ORDER BY sale_day DESC
");
$stmt->execute([$start_date, $end_date]);
$daily_sales = $stmt->fetchAll();

// Calculate totals
$total_revenue = array_sum(array_column($daily_sales, 'daily_revenue'));
$total_transactions = array_sum(array_column($daily_sales, 'total_transactions'));
$total_items = array_sum(array_column($daily_sales, 'items_sold'));
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Sales Report</h2>
    </div>
    
    <div class="card-body">
        <!-- Date Filter -->
        <form method="GET" class="filter-form" style="margin-bottom: 2rem;">
            <div style="display: flex; gap: 1rem; align-items: end;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Start Date</label>
                    <input type="date" name="start_date" class="form-control" 
                           value="<?php echo clean($start_date); ?>">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">End Date</label>
                    <input type="date" name="end_date" class="form-control" 
                           value="<?php echo clean($end_date); ?>">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </form>

        <!-- Summary Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 8px;">
                <div style="font-size: 0.875rem; opacity: 0.9;">Total Revenue</div>
                <div style="font-size: 2rem; font-weight: bold; margin-top: 0.5rem;">
                    ETB <?php echo number_format($total_revenue, 2); ?>
                </div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 1.5rem; border-radius: 8px;">
                <div style="font-size: 0.875rem; opacity: 0.9;">Transactions</div>
                <div style="font-size: 2rem; font-weight: bold; margin-top: 0.5rem;">
                    <?php echo number_format($total_transactions); ?>
                </div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 1.5rem; border-radius: 8px;">
                <div style="font-size: 0.875rem; opacity: 0.9;">Items Sold</div>
                <div style="font-size: 2rem; font-weight: bold; margin-top: 0.5rem;">
                    <?php echo number_format($total_items); ?>
                </div>
            </div>
        </div>

        <!-- Daily Sales Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transactions</th>
                        <th>Items Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($daily_sales)): ?>
                    <tr>
                        <td colspan="4" style="text-align: center; padding: 2rem; color: var(--text-secondary);">
                            No sales data for selected period
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($daily_sales as $day): ?>
                        <tr>
                            <td><strong><?php echo date('M d, Y', strtotime($day['sale_day'])); ?></strong></td>
                            <td><?php echo $day['total_transactions']; ?></td>
                            <td><?php echo $day['items_sold']; ?></td>
                            <td><strong>ETB <?php echo number_format($day['daily_revenue'], 2); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
