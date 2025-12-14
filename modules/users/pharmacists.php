<?php

// HARAMAYA PHARMA - Pharmacist Management
//Manage Pharmacist accounts

$page_title = 'Pharmacist Management';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../templates/header.php';

// Check admin permission
require_role('admin');

// Get pharmacist users
$pharmacists = $pdo->query("
    SELECT user_id, username, full_name, email, is_active, created_at, last_login
    FROM users 
    WHERE role = 'pharmacist'
    ORDER BY full_name
")->fetchAll();
?>

<div class="page-header">
    <h1><i class="fas fa-user-md"></i> Pharmacist Management</h1>
    <div class="page-actions">
        <a href="manage.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to All Users
        </a>
        <button class="btn btn-primary" onclick="window.location.href='manage.php'">
            <i class="fas fa-plus"></i> Add Pharmacist
        </button>
    </div>
</div>

<!-- Pharmacist Stats -->
<div class="stats-grid" style="margin-bottom: 2rem;">
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-user-md"></i> Total Pharmacists</div>
        <div class="stat-value"><?php echo count($pharmacists); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-check-circle"></i> Active Pharmacists</div>
        <div class="stat-value"><?php echo count(array_filter($pharmacists, fn($p) => $p['is_active'])); ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-clock"></i> Recently Active</div>
        <div class="stat-value"><?php echo count(array_filter($pharmacists, fn($p) => $p['last_login'] && strtotime($p['last_login']) > strtotime('-7 days'))); ?></div>
    </div>
</div>

<!-- Pharmacists Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Licensed Pharmacists</h2>
        <p class="card-subtitle">Manage pharmacist accounts with prescription and inventory access</p>
    </div>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Pharmacist</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>License Status</th>
                    <th>Created</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pharmacists)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 2rem;">
                        <i class="fas fa-user-md" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem; display: block;"></i>
                        No pharmacists found
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($pharmacists as $pharmacist): ?>
                    <tr>
                        <td>
                            <div class="user-info">
                                <div class="user-avatar pharmacist-avatar">
                                    <?php echo strtoupper(substr($pharmacist['full_name'], 0, 1)); ?>
                                </div>
                                <div>
                                    <strong><?php echo clean($pharmacist['full_name']); ?></strong><br>
                                    <small>@<?php echo clean($pharmacist['username']); ?></small>
                                </div>
                            </div>
                        </td>
                        <td><?php echo clean($pharmacist['email']); ?></td>
                        <td>
                            <span class="badge <?php echo $pharmacist['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                <i class="fas fa-<?php echo $pharmacist['is_active'] ? 'check-circle' : 'times-circle'; ?>"></i>
                                <?php echo $pharmacist['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-success">
                                <i class="fas fa-certificate"></i> Licensed
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($pharmacist['created_at'])); ?></td>
                        <td>
                            <?php if ($pharmacist['last_login']): ?>
                                <?php echo date('M d, Y H:i', strtotime($pharmacist['last_login'])); ?>
                            <?php else: ?>
                                <span class="text-muted">Never</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-sm btn-primary" onclick="editPharmacist(<?php echo $pharmacist['user_id']; ?>)" title="Edit Pharmacist">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-info" onclick="viewLicense(<?php echo $pharmacist['user_id']; ?>)" title="View License">
                                    <i class="fas fa-certificate"></i>
                                </button>
                                <button class="btn btn-sm btn-secondary" onclick="viewActivity(<?php echo $pharmacist['user_id']; ?>)" title="View Activity">
                                    <i class="fas fa-chart-line"></i>
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

<!-- Pharmacist Responsibilities -->
<div class="card" style="margin-top: 2rem;">
    <div class="card-header">
        <h3><i class="fas fa-tasks"></i> Pharmacist Responsibilities</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            <div class="responsibility-item">
                <i class="fas fa-prescription-bottle-alt"></i>
                <h4>Prescription Management</h4>
                <p>Review and validate prescriptions, ensure proper dosage and drug interactions</p>
            </div>
            <div class="responsibility-item">
                <i class="fas fa-boxes"></i>
                <h4>Inventory Control</h4>
                <p>Manage stock levels, expiry dates, and ensure proper storage conditions</p>
            </div>
            <div class="responsibility-item">
                <i class="fas fa-user-check"></i>
                <h4>Patient Counseling</h4>
                <p>Provide medication guidance and answer patient questions about treatments</p>
            </div>
            <div class="responsibility-item">
                <i class="fas fa-clipboard-check"></i>
                <h4>Quality Assurance</h4>
                <p>Ensure compliance with pharmaceutical regulations and safety standards</p>
            </div>
        </div>
    </div>
</div>

<!-- Placeholder Notice -->
<div class="card" style="margin-top: 2rem;">
    <div class="card-body" style="text-align: center; padding: 2rem;">
        <i class="fas fa-tools" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
        <h3>Pharmacist Management Features</h3>
        <p style="color: var(--text-secondary); margin-bottom: 1.5rem;">
            This page is ready for implementation. Features to be added:
        </p>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; text-align: left;">
            <div class="feature-item">
                <i class="fas fa-plus-circle"></i> Add new pharmacists
            </div>
            <div class="feature-item">
                <i class="fas fa-certificate"></i> License management
            </div>
            <div class="feature-item">
                <i class="fas fa-chart-line"></i> Performance tracking
            </div>
            <div class="feature-item">
                <i class="fas fa-graduation-cap"></i> Training records
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

.pharmacist-avatar {
    background: var(--primary-color);
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.responsibility-item {
    padding: 1.5rem;
    background: var(--content-bg);
    border-radius: 8px;
    text-align: center;
    border-top: 3px solid var(--primary-color);
}

.responsibility-item i {
    font-size: 2rem;
    color: var(--primary-color);
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
    border-left: 3px solid var(--primary-color);
}

.feature-item i {
    color: var(--primary-color);
    margin-right: 0.5rem;
}
</style>

<script>
function addPharmacist() {
    // Redirect to main user management page with pharmacist pre-selected
    window.location.href = 'manage.php?role=pharmacist';
}

function editPharmacist(userId) {
    alert('Edit pharmacist functionality will be implemented here.\nUser ID: ' + userId);
}

function viewLicense(userId) {
    alert('View license functionality will be implemented here.\nUser ID: ' + userId);
}

function viewActivity(userId) {
    alert('View activity functionality will be implemented here.\nUser ID: ' + userId);
}
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
