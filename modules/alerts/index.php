<?php
/**
 * HARAMAYA PHARMA - Alerts Dashboard
 * Comprehensive view of all system alerts
 */

$page_title = 'System Alerts';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/alerts.php';
require_once __DIR__ . '/../../templates/header.php';

// Get all alerts
$low_stock_alerts = get_low_stock_alerts($pdo);
$expiry_alerts = get_expiry_alerts($pdo);
$critical_alerts = get_critical_alerts($pdo);
$alert_summary = get_alert_summary($pdo);
?>

<!-- Breadcrumb -->
<nav class="breadcrumb">
    <a href="../dashboard/index.php"><i class="fas fa-home"></i> Dashboard</a>
    <span class="breadcrumb-separator">/</span>
    <span class="breadcrumb-current">System Alerts</span>
</nav>

<div class="page-header">
    <h1><i class="fas fa-exclamation-triangle"></i> System Alerts</h1>
    <p class="page-subtitle">Monitor stock levels, expiry dates, and system warnings</p>
</div>

<!-- Alert Summary -->
<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card <?php echo $alert_summary['expired_count'] > 0 ? 'danger' : ''; ?>">
        <div class="stat-label"><i class="fas fa-times-circle"></i> Expired Items</div>
        <div class="stat-value"><?php echo $alert_summary['expired_count']; ?></div>
        <div class="stat-change">Immediate attention required</div>
    </div>
    
    <div class="stat-card <?php echo $alert_summary['critical_expiry_count'] > 0 ? 'warning' : ''; ?>">
        <div class="stat-label"><i class="fas fa-exclamation-triangle"></i> Expiring Soon</div>
        <div class="stat-value"><?php echo $alert_summary['critical_expiry_count']; ?></div>
        <div class="stat-change">Next 30 days</div>
    </div>
    
    <div class="stat-card <?php echo $alert_summary['low_stock_count'] > 0 ? 'warning' : ''; ?>">
        <div class="stat-label"><i class="fas fa-boxes"></i> Low Stock</div>
        <div class="stat-value"><?php echo $alert_summary['low_stock_count']; ?></div>
        <div class="stat-change">Below reorder level</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-bell"></i> Total Alerts</div>
        <div class="stat-value"><?php echo $alert_summary['total_alerts']; ?></div>
        <div class="stat-change">Active warnings</div>
    </div>
</div>

<!-- Critical Alerts -->
<?php if (!empty($critical_alerts)): ?>
<div class="card alert-card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3 style="color: var(--danger-color);"><i class="fas fa-exclamation-circle"></i> Critical Alerts</h3>
        <span class="badge badge-danger"><?php echo count($critical_alerts); ?></span>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1rem;">
            <?php foreach ($critical_alerts as $alert): ?>
            <div class="critical-alert-item">
                <div class="alert-icon">
                    <i class="<?php echo $alert['icon']; ?>"></i>
                </div>
                <div class="alert-content">
                    <div class="alert-title"><?php echo $alert['message']; ?></div>
                    <div class="alert-type"><?php echo ucfirst(str_replace('_', ' ', $alert['type'])); ?></div>
                </div>
                <a href="<?php echo $alert['url']; ?>" class="btn btn-sm btn-<?php echo $alert['color']; ?>">
                    View Details
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Low Stock Alerts -->
<?php if (!empty($low_stock_alerts)): ?>
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3><i class="fas fa-boxes"></i> Low Stock Items</h3>
        <span class="badge badge-warning"><?php echo count($low_stock_alerts); ?></span>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Current Stock</th>
                    <th>Reorder Level</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($low_stock_alerts as $item): ?>
                <tr>
                    <td>
                        <strong><?php echo clean($item['product_name']); ?></strong><br>
                        <small><?php echo clean($item['product_code']); ?></small>
                    </td>
                    <td><?php echo clean($item['category_name']); ?></td>
                    <td>
                        <span class="stock-level <?php echo $item['current_stock'] == 0 ? 'out' : 'critical'; ?>">
                            <i class="fas fa-<?php echo $item['current_stock'] == 0 ? 'times' : 'exclamation'; ?>"></i>
                            <?php echo $item['current_stock']; ?>
                        </span>
                    </td>
                    <td><?php echo $item['reorder_level']; ?></td>
                    <td>
                        <?php if ($item['current_stock'] == 0): ?>
                            <span class="badge badge-danger">Out of Stock</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Low Stock</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="../stock/add.php?product_id=<?php echo $item['product_id']; ?>" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus"></i> Restock
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Expiry Alerts -->
<?php if ($expiry_alerts['total_expired'] > 0 || $expiry_alerts['total_critical'] > 0): ?>
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-calendar-times"></i> Expiry Alerts</h3>
        <div>
            <?php if ($expiry_alerts['total_expired'] > 0): ?>
                <span class="badge badge-danger"><?php echo $expiry_alerts['total_expired']; ?> Expired</span>
            <?php endif; ?>
            <?php if ($expiry_alerts['total_critical'] > 0): ?>
                <span class="badge badge-warning"><?php echo $expiry_alerts['total_critical']; ?> Critical</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Batch</th>
                    <th>Expiry Date</th>
                    <th>Days Left</th>
                    <th>Quantity</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $all_expiry_items = array_merge($expiry_alerts['expired'], $expiry_alerts['critical']);
                foreach ($all_expiry_items as $item): 
                ?>
                <tr class="<?php echo $item['alert_type'] === 'expired' ? 'table-danger' : 'table-warning'; ?>">
                    <td>
                        <strong><?php echo clean($item['product_name']); ?></strong><br>
                        <small><?php echo clean($item['product_code']); ?></small>
                    </td>
                    <td><?php echo clean($item['batch_number']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($item['expiry_date'])); ?></td>
                    <td>
                        <?php if ($item['alert_type'] === 'expired'): ?>
                            <span class="expiry-indicator expired">
                                <i class="fas fa-times"></i>
                                <?php echo $item['days_overdue']; ?> days overdue
                            </span>
                        <?php else: ?>
                            <span class="expiry-indicator critical">
                                <i class="fas fa-exclamation-triangle"></i>
                                <?php echo $item['days_left']; ?> days left
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo $item['quantity_remaining']; ?></td>
                    <td>
                        <?php if ($item['alert_type'] === 'expired'): ?>
                            <span class="badge badge-danger">Expired</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Expiring Soon</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- No Alerts -->
<?php if ($alert_summary['total_alerts'] == 0): ?>
<div class="card">
    <div style="text-align: center; padding: 4rem;">
        <i class="fas fa-check-circle" style="font-size: 4rem; color: var(--success-color); margin-bottom: 1rem;"></i>
        <h2>All Systems Normal</h2>
        <p style="color: var(--text-secondary); font-size: 1.1rem;">No alerts or warnings at this time.</p>
        <div style="margin-top: 2rem;">
            <a href="../stock/view.php" class="btn btn-primary">
                <i class="fas fa-boxes"></i> View Stock
            </a>
            <a href="../stock/expiry-alerts.php" class="btn btn-secondary">
                <i class="fas fa-calendar-check"></i> Check Expiry Dates
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.critical-alert-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--content-bg);
    border-radius: 8px;
    border-left: 4px solid var(--danger-color);
}

.alert-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: var(--danger-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.alert-content {
    flex: 1;
}

.alert-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.alert-type {
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.table-danger {
    background: rgba(239, 68, 68, 0.1);
}

.table-warning {
    background: rgba(245, 158, 11, 0.1);
}
</style>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>