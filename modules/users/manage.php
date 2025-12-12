<?php
/**
 * HARAMAYA PHARMA - User Management
 * Manage Admin, Pharmacist, and Cashier accounts
 */

$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';

secure_session_start();
require_login();
require_role('admin');

$current_user = get_logged_user();
$message = '';
$error = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token';
    } else {
        $action = $_POST['action'] ?? '';
        
        // Add new user
        if ($action === 'add_user') {
            $username = sanitize_input($_POST['username'] ?? '');
            $full_name = sanitize_input($_POST['full_name'] ?? '');
            $email = sanitize_input($_POST['email'] ?? '');
            $role = $_POST['role'] ?? '';
            $password = $_POST['password'] ?? '';
            
            // Validate inputs
            if (empty($username) || empty($full_name) || empty($email) || empty($role) || empty($password)) {
                $error = 'All fields are required';
            } elseif (!in_array($role, ['pharmacist', 'cashier'])) {
                $error = 'Invalid role selected';
            } elseif (!validate_email($email)) {
                $error = 'Invalid email format';
            } elseif (strlen($password) < 6) {
                $error = 'Password must be at least 6 characters';
            } else {
                try {
                    // Check if username or email already exists
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
                    $stmt->execute([$username, $email]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = 'Username or email already exists';
                    } else {
                        // Insert new user
                        $stmt = $pdo->prepare("
                            INSERT INTO users (username, password_hash, full_name, email, role, created_by) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $username,
                            hash_password($password),
                            $full_name,
                            $email,
                            $role,
                            $current_user['user_id']
                        ]);
                        
                        // Log the action
                        log_security_event($pdo, $current_user['user_id'], 'USER_CREATED', 
                            "Created new $role: $full_name ($username)");
                        
                        $message = ucfirst($role) . ' added successfully!';
                    }
                } catch (PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        }
        
        // Delete user
        elseif ($action === 'delete_user') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            
            if ($user_id <= 0) {
                $error = 'Invalid user ID';
            } elseif ($user_id == $current_user['user_id']) {
                $error = 'You cannot delete your own account';
            } else {
                try {
                    // Get user info before deletion
                    $stmt = $pdo->prepare("SELECT username, full_name, role FROM users WHERE user_id = ?");
                    $stmt->execute([$user_id]);
                    $user_to_delete = $stmt->fetch();
                    
                    if (!$user_to_delete) {
                        $error = 'User not found';
                    } elseif ($user_to_delete['role'] === 'admin') {
                        $error = 'Cannot delete administrator accounts';
                    } else {
                        // Delete the user
                        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                        $stmt->execute([$user_id]);
                        
                        // Log the action
                        log_security_event($pdo, $current_user['user_id'], 'USER_DELETED', 
                            "Deleted {$user_to_delete['role']}: {$user_to_delete['full_name']} ({$user_to_delete['username']})");
                        
                        $message = ucfirst($user_to_delete['role']) . ' deleted successfully!';
                    }
                } catch (PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        }
        
        // Toggle user status
        elseif ($action === 'toggle_status') {
            $user_id = (int)($_POST['user_id'] ?? 0);
            $new_status = $_POST['new_status'] === 'true' ? 1 : 0;
            
            if ($user_id <= 0) {
                $error = 'Invalid user ID';
            } elseif ($user_id == $current_user['user_id']) {
                $error = 'You cannot change your own status';
            } else {
                try {
                    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
                    $stmt->execute([$new_status, $user_id]);
                    
                    $status_text = $new_status ? 'activated' : 'deactivated';
                    log_security_event($pdo, $current_user['user_id'], 'USER_STATUS_CHANGED', 
                        "User ID $user_id $status_text");
                    
                    $message = "User $status_text successfully!";
                } catch (PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
}

$page_title = 'User Management';
require_once __DIR__ . '/../../templates/header.php';

// Get all users
$users = $pdo->query("
    SELECT user_id, username, full_name, email, role, is_active, created_at, last_login
    FROM users 
    ORDER BY role, full_name
")->fetchAll();
?>

<!-- Breadcrumb -->
<nav class="breadcrumb">
    <a href="../management/index.php"><i class="fas fa-cogs"></i> Management Center</a>
    <span class="breadcrumb-separator">/</span>
    <span class="breadcrumb-current">User Management</span>
</nav>

<div class="page-header">
    <h1><i class="fas fa-users"></i> User Management</h1>
    <div class="page-actions">
        <a href="../management/index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Management Center
        </a>
        <a href="activity-log.php" class="btn btn-secondary">
            <i class="fas fa-history"></i> Activity Log
        </a>
        <button class="btn btn-primary" onclick="openAddUserModal()">
            <i class="fas fa-plus"></i> Add New User
        </button>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo clean($message); ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo clean($error); ?>
    </div>
<?php endif; ?>

<!-- Users Overview Cards -->
<div class="stats-grid" style="margin-bottom: 2rem;">
    <?php
    $role_counts = [];
    foreach ($users as $user) {
        $role_counts[$user['role']] = ($role_counts[$user['role']] ?? 0) + 1;
    }
    ?>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-user-shield"></i> Administrators</div>
        <div class="stat-value"><?php echo $role_counts['admin'] ?? 0; ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-user-md"></i> Pharmacists</div>
        <div class="stat-value"><?php echo $role_counts['pharmacist'] ?? 0; ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-cash-register"></i> Cashiers</div>
        <div class="stat-value"><?php echo $role_counts['cashier'] ?? 0; ?></div>
    </div>
    
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-users"></i> Total Users</div>
        <div class="stat-value"><?php echo count($users); ?></div>
    </div>
</div>

<!-- Quick Actions -->
<div class="card" style="margin-bottom: 2rem;">
    <div class="card-header">
        <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
            <button class="quick-action-btn" onclick="openAddUserModal(); document.querySelector('select[name=role]').value='pharmacist';">
                <i class="fas fa-user-md"></i>
                <span>Add Pharmacist</span>
                <small>Licensed pharmacy professional</small>
            </button>
            <button class="quick-action-btn" onclick="openAddUserModal(); document.querySelector('select[name=role]').value='cashier';">
                <i class="fas fa-cash-register"></i>
                <span>Add Cashier</span>
                <small>Point of sale operator</small>
            </button>
            <a href="activity-log.php" class="quick-action-btn">
                <i class="fas fa-history"></i>
                <span>View Activity Log</span>
                <small>System activity tracking</small>
            </a>
            <a href="../reports/sales-report.php" class="quick-action-btn">
                <i class="fas fa-chart-bar"></i>
                <span>User Performance</span>
                <small>Sales and activity reports</small>
            </a>
        </div>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">System Users</h2>
        <div class="card-actions">
            <input type="text" id="userSearch" placeholder="Search users..." class="form-control" style="width: 250px;">
        </div>
    </div>
    
    <div class="table-container">
        <table class="data-table" id="usersTable">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <div class="user-info">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <strong><?php echo clean($user['full_name']); ?></strong><br>
                                <small>@<?php echo clean($user['username']); ?></small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-<?php 
                            echo $user['role'] === 'admin' ? 'danger' : 
                                ($user['role'] === 'pharmacist' ? 'primary' : 'secondary'); 
                        ?>">
                            <i class="fas fa-<?php 
                                echo $user['role'] === 'admin' ? 'user-shield' : 
                                    ($user['role'] === 'pharmacist' ? 'user-md' : 'cash-register'); 
                            ?>"></i>
                            <?php echo ucfirst(clean($user['role'])); ?>
                        </span>
                    </td>
                    <td><?php echo clean($user['email']); ?></td>
                    <td>
                        <span class="badge <?php echo $user['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                            <i class="fas fa-<?php echo $user['is_active'] ? 'check-circle' : 'times-circle'; ?>"></i>
                            <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($user['last_login']): ?>
                            <?php echo date('M d, Y H:i', strtotime($user['last_login'])); ?>
                        <?php else: ?>
                            <span class="text-muted">Never</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn btn-sm btn-primary" onclick="editUser(<?php echo $user['user_id']; ?>)" title="Edit User">
                                <i class="fas fa-edit"></i>
                            </button>
                            <?php if ($user['user_id'] != $current_user['user_id']): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to <?php echo $user['is_active'] ? 'deactivate' : 'activate'; ?> this user?')">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <input type="hidden" name="new_status" value="<?php echo $user['is_active'] ? 'false' : 'true'; ?>">
                                <button type="submit" class="btn btn-sm btn-<?php echo $user['is_active'] ? 'warning' : 'success'; ?>" 
                                        title="<?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?> User">
                                    <i class="fas fa-<?php echo $user['is_active'] ? 'user-slash' : 'user-check'; ?>"></i>
                                </button>
                            </form>
                            <?php if ($user['role'] !== 'admin'): ?>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete <?php echo clean($user['full_name']); ?>? This action cannot be undone.')">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete <?php echo ucfirst($user['role']); ?>">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div id="addUserModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-user-plus"></i> Add New User</h3>
            <button class="modal-close" onclick="closeAddUserModal()">&times;</button>
        </div>
        <div class="modal-body">
            <form method="POST" id="addUserForm">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="add_user">
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Full Name
                    </label>
                    <input type="text" name="full_name" class="form-control" required 
                           placeholder="Enter full name">
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-at"></i> Username
                    </label>
                    <input type="text" name="username" class="form-control" required 
                           placeholder="Enter username" pattern="[a-zA-Z0-9_]+" 
                           title="Username can only contain letters, numbers, and underscores">
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-envelope"></i> Email
                    </label>
                    <input type="email" name="email" class="form-control" required 
                           placeholder="Enter email address">
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user-tag"></i> Role
                    </label>
                    <select name="role" class="form-control" required>
                        <option value="">Select Role</option>
                        <option value="pharmacist">
                            <i class="fas fa-user-md"></i> Pharmacist
                        </option>
                        <option value="cashier">
                            <i class="fas fa-cash-register"></i> Cashier
                        </option>
                    </select>
                    <small class="form-text">Note: Only Pharmacists and Cashiers can be added</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" name="password" class="form-control" required 
                           placeholder="Enter password" minlength="6">
                    <small class="form-text">Minimum 6 characters</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> Confirm Password
                    </label>
                    <input type="password" name="confirm_password" class="form-control" required 
                           placeholder="Confirm password">
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeAddUserModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add User
                    </button>
                </div>
            </form>
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
    background: var(--primary-color);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 1.1rem;
}

.action-buttons {
    display: flex;
    gap: 0.25rem;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    cursor: pointer;
    color: var(--text-secondary);
}

.modal-body {
    padding: 1.5rem;
}

.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1.5rem;
    padding-top: 1rem;
    border-top: 1px solid var(--border-color);
}

.form-text {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.alert {
    padding: 1rem;
    border-radius: 6px;
    margin-bottom: 1.5rem;
    border: 1px solid transparent;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border-color: #a7f3d0;
}

.alert-danger {
    background: #fee2e2;
    color: #991b1b;
    border-color: #fecaca;
}

.alert i {
    margin-right: 0.5rem;
}

.quick-action-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1.5rem;
    background: var(--content-bg);
    border: 2px solid var(--border-color);
    border-radius: 8px;
    text-decoration: none;
    color: var(--text-primary);
    transition: all 0.3s ease;
    cursor: pointer;
}

.quick-action-btn:hover {
    border-color: var(--primary-color);
    background: var(--primary-color);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.quick-action-btn i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.quick-action-btn span {
    font-weight: 600;
    margin-bottom: 0.25rem;
}

.quick-action-btn small {
    font-size: 0.8rem;
    opacity: 0.8;
    text-align: center;
}
</style>

<script>
// Search functionality
document.getElementById('userSearch').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#usersTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Modal functions
function openAddUserModal() {
    document.getElementById('addUserModal').style.display = 'flex';
    document.querySelector('input[name="full_name"]').focus();
    
    // Pre-select role if specified in URL
    const urlParams = new URLSearchParams(window.location.search);
    const preselectedRole = urlParams.get('role');
    if (preselectedRole && ['pharmacist', 'cashier'].includes(preselectedRole)) {
        document.querySelector('select[name="role"]').value = preselectedRole;
    }
}

function closeAddUserModal() {
    document.getElementById('addUserModal').style.display = 'none';
    document.getElementById('addUserForm').reset();
}

// Form validation
document.getElementById('addUserForm').addEventListener('submit', function(e) {
    const password = document.querySelector('input[name="password"]').value;
    const confirmPassword = document.querySelector('input[name="confirm_password"]').value;
    
    if (password !== confirmPassword) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
    
    if (password.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding User...';
    submitBtn.disabled = true;
    
    // Re-enable button after a delay (in case of validation errors)
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 3000);
});

// Close modal when clicking outside
document.getElementById('addUserModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeAddUserModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddUserModal();
    }
});

// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
    
    // Auto-open modal if role is specified in URL
    const urlParams = new URLSearchParams(window.location.search);
    const preselectedRole = urlParams.get('role');
    if (preselectedRole && ['pharmacist', 'cashier'].includes(preselectedRole)) {
        openAddUserModal();
    }
});
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>