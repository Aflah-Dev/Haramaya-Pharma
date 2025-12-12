<?php
/**
 * HARAMAYA PHARMA - Administrator Management
 * Manage Administrator accounts
 */

$page_title = 'Administrator Management';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../templates/header.php';

// Check admin permission
require_role('admin');

// Get admin users
$admins = $pdo->query("
    SELECT user_id, username, full_name, email, is_active, created_at, last_login
    FROM users 
    WHERE role = 'admin'
    ORDER BY full_name
")->fetchAll();
?>

<div class="page-header">
    <h1><i class="fas fa-user-shield"></i> Administrator Management</h1>
    <div class="page-actions">
        <a href="manage.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to All Users
        </a>
        <button class="btn btn-primary" onclick="addAdmin()">
            <i class="fas fa-plus"></i> Add Administrator
        </button>
    </div>
</div>

<!-- Admin Stats -->
<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-user-shield"></i> Total Administrators</div>
        <div class="stat-value"><?php echo count($admins); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-check-circle"></i> Active Administrators</div>
        <div class="stat-value"><?php echo count(array_filter($admins, fn($a) => $a['is_active'])); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-clock"></i> Recently Active</div>
        <div class="stat-value"><?php echo count(array_filter($admins, fn($a) => $a['last_login'] && strtotime($a['last_login']) > strtotime('-7 days'))); ?></div>
    </div>
</div>

<!-- Administrators Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">System Administrators</h2>
        <p class="card-subtitle">Manage users with full system access</p>
    </div>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Administrator</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($admins)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-user-shield" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem; display: block;"></i>
                        No administrators found
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($admins as $admin): ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar admin-avatar">
                                    <?php echo strtoupper(substr($admin['full_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <strong><?php echo clean($admin['full_name']); ?></strong><br>
                                    <small>@<?php echo clean($admin['username']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?php echo clean($admin['email']); ?></td>
                        <td>
                            <span class="badge <?php echo $admin['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                <i class="fas fa-<?php echo $admin['is_active'] ? 'check-circle' : 'times-circle'; ?>"></i>
                                <?php echo $admin['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($admin['created_at'])); ?></td>
                        <td>
                            <?php if ($admin['last_login']): ?>
                                <?php echo date('M d, Y H:i', strtotime($admin['last_login'])); ?>
                            <?php else: ?>
                                <span class="text-muted">Never</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="editAdmin(<?php echo $admin['user_id']; ?>)" title="Edit Administrator">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($admin['user_id'] != $current_user['user_id']): ?>
                                <button class="btn btn-sm btn-warning" onclick="managePermissions(<?php echo $admin['user_id']; ?>)" title="Manage Permissions">
                                    <i class="fas fa-key"></i>
                                </button>
                                <?php endif; ?>
                            </div>
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
        <h3>Administrator Management Features</h3>
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
            This page is ready for implementation. Features to be added:
        </p>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; text-align: left;">
            <div class="feature-item">
                <i class="fas fa-plus-circle"></i> Add new administrators
            </div>
            <div class="feature-item">
                <i class="fas fa-edit"></i> Edit administrator profiles
            </div>
            <div class="feature-item">
                <i class="fas fa-key"></i> Manage permissions
            </div>
            <div class="feature-item">
                <i class="fas fa-shield-alt"></i> Security settings
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

.admin-avatar {
    background: var(--danger-color);
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.feature-item {
    padding: 0.75rem;
    background: var(--content-bg);
    border-radius: 6px;
    border-left: 3px solid var(--danger-color);
}

.feature-item i {
    color: var(--danger-color);
    margin-right: 0.5rem;
}
</style>

<script>
function addAdmin() {
    alert('Add administrator functionality will be implemented here.');
}

function editAdmin(userId) {
    alert('Edit administrator functionality will be implemented here.\nUser ID: ' + userId);
}

function managePermissions(userId) {
    alert('Manage permissions functionality will be implemented here.\nUser ID: ' + userId);
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>