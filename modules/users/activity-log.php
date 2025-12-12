<?php
/**
 * HARAMAYA PHARMA - User Activity Log
 * Track user actions and system events
 */

$page_title = 'Activity Log';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../templates/header.php';

// Check admin permission
require_role('admin');

// Get recent activity logs
$logs = $pdo->query("
    SELECT al.*, u.full_name, u.username, u.role
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.user_id
    ORDER BY al.created_at DESC
    LIMIT 100
")->fetchAll();
?>

<div class="page-header">
    <h1><i class="fas fa-history"></i> User Activity Log</h1>
    <div class="page-actions">
        <a href="manage.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Users
        </a>
        <button class="btn btn-primary" onclick="exportLog()">
            <i class="fas fa-download"></i> Export Log
        </button>
    </div>
</div>

<!-- Activity Stats -->
<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-list"></i> Total Activities</div>
        <div class="stat-value"><?php echo count($logs); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-clock"></i> Today's Activities</div>
        <div class="stat-value"><?php echo count(array_filter($logs, fn($l) => date('Y-m-d', strtotime($l['created_at'])) === date('Y-m-d'))); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-sign-in-alt"></i> Login Events</div>
        <div class="stat-value"><?php echo count(array_filter($logs, fn($l) => $l['action'] === 'LOGIN')); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-shopping-cart"></i> Sales Events</div>
        <div class="stat-value"><?php echo count(array_filter($logs, fn($l) => $l['action'] === 'SALE_COMPLETED')); ?></div>
    </div>
</div>

<!-- Activity Log Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Recent System Activity</h2>
        <div class="card-actions">
            <select id="actionFilter" class="form-control" style="width: 150px;">
                <option value="">All Actions</option>
                <option value="LOGIN">Login</option>
                <option value="LOGOUT">Logout</option>
                <option value="SALE_COMPLETED">Sales</option>
                <option value="STOCK_ADDED">Stock Added</option>
                <option value="PRODUCT_CREATED">Product Created</option>
            </select>
            <input type="text" id="activitySearch" placeholder="Search activities..." class="form-control" style="width: 200px;">
        </div>
    </div>
    
    <div class="table-container">
        <table class="data-table" id="activityTable">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-history" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem; display: block;"></i>
                        No activity logs found
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                    <tr data-action="<?php echo clean($log['action']); ?>">
                        <td>
                            <div style="font-size: 0.9rem;">
                                <?php echo date('M d, Y', strtotime($log['created_at'])); ?><br>
                                <small style="color: var(--text-secondary);"><?php echo date('H:i:s', strtotime($log['created_at'])); ?></small>
                            </div>
                        </td>
                        <td>
                            <?php if ($log['full_name']): ?>
                                <div class="user-info">
                                    <div class="user-avatar-small <?php echo $log['role']; ?>-avatar">
                                        <?php echo strtoupper(substr($log['full_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <strong><?php echo clean($log['full_name']); ?></strong><br>
                                        <small>@<?php echo clean($log['username']); ?></small>
                                    </div>
                                </div>
                            <?php else: ?>
                                <span class="text-muted">System</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="action-badge action-<?php echo strtolower($log['action']); ?>">
                                <i class="fas fa-<?php 
                                    echo $log['action'] === 'LOGIN' ? 'sign-in-alt' : 
                                        ($log['action'] === 'LOGOUT' ? 'sign-out-alt' : 
                                        ($log['action'] === 'SALE_COMPLETED' ? 'shopping-cart' : 
                                        ($log['action'] === 'STOCK_ADDED' ? 'boxes' : 'cog'))); 
                                ?>"></i>
                                <?php echo clean($log['action']); ?>
                            </span>
                        </td>
                        <td>
                            <div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
                                <?php echo clean($log['details']); ?>
                            </div>
                        </td>
                        <td>
                            <code><?php echo clean($log['ip_address']); ?></code>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Placeholder Notice -->
<div class="card" style="margin-top: 2rem;">
    <div class="card-body" style="text-align: center; padding: 2rem;">
        <i class="fas fa-tools" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
        <h3>Activity Log Features</h3>
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
            This page is ready for implementation. Features to be added:
        </p>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; text-align: left;">
            <div class="feature-item">
                <i class="fas fa-download"></i> Export activity logs
            </div>
            <div class="feature-item">
                <i class="fas fa-filter"></i> Advanced filtering
            </div>
            <div class="feature-item">
                <i class="fas fa-chart-line"></i> Activity analytics
            </div>
            <div class="feature-item">
                <i class="fas fa-bell"></i> Activity alerts
            </div>
        </div>
    </div>
</div>

<style>
.user-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.user-avatar-small {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.9rem;
}

.admin-avatar { background: var(--danger-color); }
.pharmacist-avatar { background: var(--primary-color); }
.cashier-avatar { background: var(--secondary-color); }

.action-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: bold;
    color: white;
}

.action-login { background: var(--success-color); }
.action-logout { background: var(--warning-color); }
.action-sale_completed { background: var(--primary-color); }
.action-stock_added { background: var(--secondary-color); }
.action-product_created { background: var(--info-color, #17a2b8); }

.feature-item {
    padding: 0.75rem;
    background: var(--content-bg);
    border-radius: 6px;
    border-left: 3px solid var(--primary-color);
}

.feature-item i {
    color: var(--primary-color);
    margin-right: 0.5rem;
}
</style>

<script>
// Search functionality
document.getElementById('activitySearch').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#activityTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Filter by action
document.getElementById('actionFilter').addEventListener('change', function() {
    const filterValue = this.value;
    const rows = document.querySelectorAll('#activityTable tbody tr');
    
    rows.forEach(row => {
        if (!filterValue || row.dataset.action === filterValue) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

function exportLog() {
    alert('Export activity log functionality will be implemented here.');
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>