<?php

// HARAMAYA PHARMA - Management Dashboard
//Unified management center for all administrative functions

$page_title = 'Management Center';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../templates/header.php';

// Check admin permission
require_role('admin');

// Get management statistics
$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_active = 1")->fetchColumn(),
    'products' => $pdo->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn(),
    'suppliers' => $pdo->query("SELECT COUNT(*) FROM suppliers WHERE is_active = 1")->fetchColumn(),
    'categories' => $pdo->query("SELECT COUNT(*) FROM product_categories")->fetchColumn(),
    'recent_activities' => $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn()
];

// Get recent activities
$recent_activities = $pdo->query("
    SELECT al.*, u.full_name, u.username 
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.user_id
    ORDER BY al.created_at DESC
    LIMIT 5
")->fetchAll();
?>

<div class="page-header">
    <h1><i class="fas fa-cogs"></i> Management Center</h1>
    <p class="page-subtitle">Centralized administration and system management</p>
</div>

<!-- Management Overview -->
<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-users"></i> Active Users</div>
        <div class="stat-value"><?php echo $stats['users']; ?></div>
        <div class="stat-change">System accounts</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-capsules"></i> Products</div>
        <div class="stat-value"><?php echo $stats['products']; ?></div>
        <div class="stat-change">In catalog</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-truck"></i> Suppliers</div>
        <div class="stat-value"><?php echo $stats['suppliers']; ?></div>
        <div class="stat-change">Active suppliers</div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-history"></i> Today's Activity</div>
        <div class="stat-value"><?php echo $stats['recent_activities']; ?></div>
        <div class="stat-change">System events</div>
    </div>
</div>

<!-- Management Modules -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
    
    <!-- User Management -->
    <div class="management-card user-management">
        <div class="management-header">
            <i class="fas fa-users"></i>
            <h3>User Management</h3>
        </div>
        <div class="management-body">
            <p>Manage system users, roles, and permissions</p>
            <div class="management-actions">
                <a href="../users/manage.php" class="btn btn-primary">
                    <i class="fas fa-users"></i> All Users
                </a>
                <a href="../users/admins.php" class="btn btn-outline">
                    <i class="fas fa-user-shield"></i> Admins
                </a>
                <a href="../users/pharmacists.php" class="btn btn-outline">
                    <i class="fas fa-user-md"></i> Pharmacists
                </a>
                <a href="../users/cashiers.php" class="btn btn-outline">
                    <i class="fas fa-cash-register"></i> Cashiers
                </a>
            </div>
        </div>
    </div>
    
    <!-- Product Management -->
    <div class="management-card product-management">
        <div class="management-header">
            <i class="fas fa-capsules"></i>
            <h3>Product Management</h3>
        </div>
        <div class="management-body">
            <p>Manage products, categories, and inventory</p>
            <div class="management-actions">
                <a href="../products/manage.php" class="btn btn-primary">
                    <i class="fas fa-capsules"></i> Products
                </a>
                <a href="../products/categories.php" class="btn btn-outline">
                    <i class="fas fa-tags"></i> Categories
                </a>
                <a href="../stock/view.php" class="btn btn-outline">
                    <i class="fas fa-boxes"></i> Stock
                </a>
                <a href="../stock/add.php" class="btn btn-outline">
                    <i class="fas fa-plus-circle"></i> Add Stock
                </a>
            </div>
        </div>
    </div>
    
    <!-- Supplier Management -->
    <div class="management-card supplier-management">
        <div class="management-header">
            <i class="fas fa-truck"></i>
            <h3>Supplier Management</h3>
        </div>
        <div class="management-body">
            <p>Manage suppliers and vendor relationships</p>
            <div class="management-actions">
                <a href="../suppliers/manage.php" class="btn btn-primary">
                    <i class="fas fa-truck"></i> All Suppliers
                </a>
                <a href="../suppliers/manage.php?action=add" class="btn btn-outline">
                    <i class="fas fa-plus"></i> Add Supplier
                </a>
                <a href="../reports/supplier-report.php" class="btn btn-outline">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </div>
        </div>
    </div>
    
    <!-- System Management -->
    <div class="management-card system-management">
        <div class="management-header">
            <i class="fas fa-cog"></i>
            <h3>System Management</h3>
        </div>
        <div class="management-body">
            <p>System settings, logs, and maintenance</p>
            <div class="management-actions">
                <a href="../users/activity-log.php" class="btn btn-primary">
                    <i class="fas fa-history"></i> Activity Log
                </a>
                <a href="../reports/sales-report.php" class="btn btn-outline">
                    <i class="fas fa-chart-line"></i> Reports
                </a>
                <a href="#" class="btn btn-outline" onclick="alert('System settings will be implemented')">
                    <i class="fas fa-cogs"></i> Settings
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-clock"></i> Recent System Activity</h3>
        <a href="../users/activity-log.php" class="btn btn-secondary">View All</a>
    </div>
    <div class="card-body">
        <?php if (empty($recent_activities)): ?>
            <p style="text-align: center; color: var(--text-secondary); padding: 2rem;">
                <i class="fas fa-history" style="font-size: 3rem; margin-bottom: 1rem; display: block;"></i>
                No recent activity
            </p>
        <?php else: ?>
            <div class="activity-list">
                <?php foreach ($recent_activities as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <i class="fas fa-<?php 
                            echo $activity['action'] === 'LOGIN' ? 'sign-in-alt' : 
                                ($activity['action'] === 'LOGOUT' ? 'sign-out-alt' : 
                                ($activity['action'] === 'SALE_COMPLETED' ? 'shopping-cart' : 
                                ($activity['action'] === 'USER_CREATED' ? 'user-plus' : 'cog'))); 
                        ?>"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">
                            <?php if ($activity['full_name']): ?>
                                <strong><?php echo clean($activity['full_name']); ?></strong>
                            <?php else: ?>
                                <strong>System</strong>
                            <?php endif; ?>
                            <span class="activity-action"><?php echo clean($activity['action']); ?></span>
                        </div>
                        <div class="activity-details"><?php echo clean($activity['details']); ?></div>
                        <div class="activity-time"><?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Quick Actions -->
<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <button class="quick-action-btn" onclick="window.location.href='../users/manage.php?role=pharmacist'">
                <i class="fas fa-user-md"></i>
                <span>Add Pharmacist</span>
            </button>
            <button class="quick-action-btn" onclick="window.location.href='../users/manage.php?role=cashier'">
                <i class="fas fa-cash-register"></i>
                <span>Add Cashier</span>
            </button>
            <button class="quick-action-btn" onclick="window.location.href='../products/manage.php'">
                <i class="fas fa-plus-circle"></i>
                <span>Add Product</span>
            </button>
            <button class="quick-action-btn" onclick="window.location.href='../suppliers/manage.php'">
                <i class="fas fa-truck"></i>
                <span>Add Supplier</span>
            </button>
            <button class="quick-action-btn" onclick="window.location.href='../stock/add.php'">
                <i class="fas fa-boxes"></i>
                <span>Add Stock</span>
            </button>
            <button class="quick-action-btn" onclick="window.location.href='../reports/sales-report.php'">
                <i class="fas fa-chart-bar"></i>
                <span>View Reports</span>
            </button>
        </div>
    </div>
</div>

<style>
.page-subtitle {
    color: var(--text-secondary);
    font-size: 1.1rem;
    margin-top: 0.5rem;
}

.stat-change {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.management-card {
    background: var(--card-bg);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow);
    transition: all 0.3s ease;
    border-top: 4px solid var(--primary-color);
}

.management-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.user-management { border-top-color: var(--danger-color); }
.product-management { border-top-color: var(--primary-color); }
.supplier-management { border-top-color: var(--secondary-color); }
.system-management { border-top-color: var(--warning-color); }

.management-header {
    padding: 1.5rem;
    background: linear-gradient(135deg, var(--content-bg), white);
    display: flex;
    align-items: center;
    gap: 1rem;
}

.management-header i {
    font-size: 2rem;
    color: var(--primary-color);
}

.management-header h3 {
    margin: 0;
    color: var(--text-primary);
}

.management-body {
    padding: 1.5rem;
}

.management-body p {
    color: var(--text-secondary);
    margin-bottom: 1.5rem;
}

.management-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.btn-outline {
    background: transparent;
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    padding: 0.5rem 1rem;
    border-radius: 6px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.btn-outline:hover {
    background: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

.activity-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem;
    background: var(--content-bg);
    border-radius: 8px;
    border-left: 3px solid var(--primary-color);
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.activity-action {
    background: var(--secondary-color);
    color: white;
    padding: 0.125rem 0.5rem;
    border-radius: 12px;
    font-size: 0.75rem;
    margin-left: 0.5rem;
}

.activity-details {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.activity-time {
    color: var(--text-secondary);
    font-size: 0.8rem;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1rem;
    background: var(--content-bg);
    border: 2px solid var(--border-color);
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    color: var(--text-primary);
}

.quick-action-btn:hover {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
}

.quick-action-btn i {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

.quick-action-btn span {
    font-weight: 600;
    font-size: 0.9rem;
}
</style>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>