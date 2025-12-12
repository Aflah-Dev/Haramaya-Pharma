<?php
/**
 * HARAMAYA PHARMA - Cashier Management
 * Manage Cashier accounts
 */

$page_title = 'Cashier Management';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../templates/header.php';

// Check admin permission
require_role('admin');

// Get cashier users
$cashiers = $pdo->query("
    SELECT user_id, username, full_name, email, is_active, created_at, last_login
    FROM users 
    WHERE role = 'cashier'
    ORDER BY full_name
")->fetchAll();

// Get sales statistics for cashiers
$sales_stats = [];
foreach ($cashiers as $cashier) {
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_sales,
            COALESCE(SUM(total_amount), 0) as total_revenue,
            COUNT(CASE WHEN DATE(sale_date) = CURDATE() THEN 1 END) as today_sales
        FROM sales 
        WHERE cashier_id = ?
    ");
    $stmt->execute([$cashier['user_id']]);
    $sales_stats[$cashier['user_id']] = $stmt->fetch();
}
?>

<div class="page-header">
    <h1><i class="fas fa-cash-register"></i> Cashier Management</h1>
    <div class="page-actions">
        <a href="manage.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to All Users
        </a>
        <button class="btn btn-primary" onclick="window.location.href='manage.php'">
            <i class="fas fa-plus"></i> Add Cashier
        </button>
    </div>
</div>

<!-- Cashier Stats -->
<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-cash-register"></i> Total Cashiers</div>
        <div class="stat-value"><?php echo count($cashiers); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-check-circle"></i> Active Cashiers</div>
        <div class="stat-value"><?php echo count(array_filter($cashiers, fn($c) => $c['is_active'])); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-clock"></i> Recently Active</div>
        <div class="stat-value"><?php echo count(array_filter($cashiers, fn($c) => $c['last_login'] && strtotime($c['last_login']) > strtotime('-7 days'))); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-chart-line"></i> Total Sales Today</div>
        <div class="stat-value"><?php echo array_sum(array_column($sales_stats, 'today_sales')); ?></div>
    </div>
</div>

<!-- Cashiers Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Point of Sale Cashiers</h2>
        <p class="card-subtitle">Manage cashier accounts with sales and transaction access</p>
    </div>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Cashier</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Total Sales</th>
                    <th>Revenue</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($cashiers)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-cash-register" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem; display: block;"></i>
                        No cashiers found
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($cashiers as $cashier): ?>
                    <?php $stats = $sales_stats[$cashier['user_id']]; ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar cashier-avatar">
                                    <?php echo strtoupper(substr($cashier['full_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <strong><?php echo clean($cashier['full_name']); ?></strong><br>
                                    <small>@<?php echo clean($cashier['username']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?php echo clean($cashier['email']); ?></td>
                        <td>
                            <span class="badge <?php echo $cashier['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                <i class="fas fa-<?php echo $cashier['is_active'] ? 'check-circle' : 'times-circle'; ?>"></i>
                                <?php echo $cashier['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <strong><?php echo number_format($stats['total_sales']); ?></strong> sales
                            <?php if ($stats['today_sales'] > 0): ?>
                                <br><small class="text-success"><?php echo $stats['today_sales']; ?> today</small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong>ETB <?php echo number_format($stats['total_revenue'], 2); ?></strong>
                        </td>
                        <td>
                            <?php if ($cashier['last_login']): ?>
                                <?php echo date('M d, Y H:i', strtotime($cashier['last_login'])); ?>
                            <?php else: ?>
                                <span class="text-muted">Never</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="editCashier(<?php echo $cashier['user_id']; ?>)" title="Edit Cashier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-info" onclick="viewSalesReport(<?php echo $cashier['user_id']; ?>)" title="Sales Report">
                                    <i class="fas fa-chart-bar"></i>
                                </button>
                                <button class="btn btn-sm btn-secondary" onclick="viewShifts(<?php echo $cashier['user_id']; ?>)" title="View Shifts">
                                    <i class="fas fa-clock"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Cashier Performance -->
<?php if (!empty($cashiers)): ?>
<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3><i class="fas fa-trophy"></i> Top Performing Cashiers</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            <?php
            // Sort cashiers by total revenue
            $top_cashiers = $cashiers;
            usort($top_cashiers, function($a, $b) use ($sales_stats) {
                return $sales_stats[$b['user_id']]['total_revenue'] <=> $sales_stats[$a['user_id']]['total_revenue'];
            });
            $top_cashiers = array_slice($top_cashiers, 0, 3);
            ?>
            
            <?php foreach ($top_cashiers as $index => $cashier): ?>
            <?php $stats = $sales_stats[$cashier['user_id']]; ?>
            <div class="performance-card">
                <div class="performance-rank">
                    <i class="fas fa-<?php echo $index === 0 ? 'crown' : ($index === 1 ? 'medal' : 'award'); ?>"></i>
                    #<?php echo $index + 1; ?>
                </div>
                <h4><?php echo clean($cashier['full_name']); ?></h4>
                <div class="performance-stats">
                    <div class="stat">
                        <span class="stat-value"><?php echo number_format($stats['total_sales']); ?></span>
                        <span class="stat-label">Total Sales</span>
                    </div>
                    <div class="stat">
                        <span class="stat-value">ETB <?php echo number_format($stats['total_revenue'], 0); ?></span>
                        <span class="stat-label">Revenue</span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Cashier Responsibilities -->
<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3><i class="fas fa-tasks"></i> Cashier Responsibilities</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            <div class="responsibility-item">
                <i class="fas fa-cash-register"></i>
                <h4>Point of Sale Operations</h4>
                <p>Process customer transactions, handle payments, and issue receipts</p>
            </div>
            <div class="responsibility-item">
                <i class="fas fa-money-bill-wave"></i>
                <h4>Cash Management</h4>
                <p>Handle cash transactions, maintain cash drawer, and balance daily sales</p>
            </div>
            <div class="responsibility-item">
                <i class="fas fa-users"></i>
                <h4>Customer Service</h4>
                <p>Assist customers with purchases and provide basic product information</p>
            </div>
            <div class="responsibility-item">
                <i class="fas fa-receipt"></i>
                <h4>Transaction Records</h4>
                <p>Maintain accurate sales records and generate daily sales reports</p>
            </div>
        </div>
    </div>
</div>

<!-- Placeholder Notice -->
<div class="card" style="margin-top: 2rem;">
    <div class="card-body" style="text-align: center; padding: 2rem;">
        <i class="fas fa-tools" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
        <h3>Cashier Management Features</h3>
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
            This page is ready for implementation. Features to be added:
        </p>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; text-align: left;">
            <div class="feature-item">
                <i class="fas fa-plus-circle"></i> Add new cashiers
            </div>
            <div class="feature-item">
                <i class="fas fa-chart-bar"></i> Sales performance tracking
            </div>
            <div class="feature-item">
                <i class="fas fa-clock"></i> Shift management
            </div>
            <div class="feature-item">
                <i class="fas fa-calculator"></i> Cash drawer reconciliation
            </div>
        </div>
    </div>
</div>

<style>
.user-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
}

.cashier-avatar {
    background: var(--secondary-color);
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.performance-card {
    background: var(--content-bg);
    padding: 1.5rem;
    border-radius: 8px;
    text-align: center;
    border-top: 3px solid var(--secondary-color);
    position: relative;
}

.performance-rank {
    position: absolute;
    top: -10px;
    right: 15px;
    background: var(--secondary-color);
    color: white;
    padding: 0.5rem;
    border-radius: 50%;
    font-size: 0.9rem;
    font-weight: bold;
}

.performance-stats {
    display: flex;
    justify-content: space-around;
    margin-top: 1rem;
}

.stat {
    text-align: center;
}

.stat-value {
    display: block;
    font-size: 1.2rem;
    font-weight: bold;
    color: var(--secondary-color);
}

.stat-label {
    font-size: 0.8rem;
    color: var(--text-secondary);
}

.responsibility-item {
    padding: 1.5rem;
    background: var(--content-bg);
    border-radius: 8px;
    text-align: center;
    border-top: 3px solid var(--secondary-color);
}

.responsibility-item i {
    font-size: 2rem;
    color: var(--secondary-color);
    margin-bottom: 1rem;
}

.responsibility-item h4 {
    margin-bottom: 0.5rem;
    color: var(--text-primary);
}

.responsibility-item p {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.feature-item {
    padding: 0.75rem;
    background: var(--content-bg);
    border-radius: 6px;
    border-left: 3px solid var(--secondary-color);
}

.feature-item i {
    color: var(--secondary-color);
    margin-right: 0.5rem;
}
</style>

<script>
function addCashier() {
    // Redirect to main user management page with cashier pre-selected
    window.location.href = 'manage.php?role=cashier';
}

function editCashier(userId) {
    alert('Edit cashier functionality will be implemented here.\nUser ID: ' + userId);
}

function viewSalesReport(userId) {
    alert('View sales report functionality will be implemented here.\nUser ID: ' + userId);
}

function viewShifts(userId) {
    alert('View shifts functionality will be implemented here.\nUser ID: ' + userId);
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>