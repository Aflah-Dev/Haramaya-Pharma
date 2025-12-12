<?php
/**
 * HARAMAYA PHARMA - Expiry Alerts
 */

$page_title = 'Expiry Alerts';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../templates/header.php';

// Handle expired product removal (Admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && has_role('admin')) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    
    $action = $_POST['action'] ?? '';
    
    if ($action === 'remove_expired_batch') {
        $batch_id = (int)$_POST['batch_id'];
        
        try {
            $pdo->beginTransaction();
            
            // Get batch details for logging
            $stmt = $pdo->prepare("
                SELECT sb.*, p.product_name 
                FROM stock_batches sb 
                INNER JOIN products p ON sb.product_id = p.product_id 
                WHERE sb.batch_id = ? AND sb.expiry_date < CURDATE()
            ");
            $stmt->execute([$batch_id]);
            $batch = $stmt->fetch();
            
            if ($batch) {
                // Instead of deleting, set quantity to 0 (safer approach)
                $update = $pdo->prepare("UPDATE stock_batches SET quantity_remaining = 0, notes = CONCAT(COALESCE(notes, ''), ' [EXPIRED - Removed by admin on ', NOW(), ']') WHERE batch_id = ?");
                $update->execute([$batch_id]);
                
                // Log the removal
                $current_user = get_logged_user();
                log_security_event(
                    $pdo, 
                    $current_user['user_id'], 
                    'EXPIRED_PRODUCT_REMOVED', 
                    "Removed expired batch: {$batch['product_name']} - Batch: {$batch['batch_number']} - Qty: {$batch['quantity_remaining']}"
                );
                
                $pdo->commit();
                echo "<script>alert('Expired batch removed successfully!'); window.location.reload();</script>";
            } else {
                throw new Exception("Batch not found or not expired");
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
    
    if ($action === 'remove_all_expired') {
        try {
            $pdo->beginTransaction();
            
            // Get count of expired batches
            $count_stmt = $pdo->query("
                SELECT COUNT(*) FROM stock_batches 
                WHERE expiry_date < CURDATE() AND quantity_remaining > 0
            ");
            $expired_count = $count_stmt->fetchColumn();
            
            if ($expired_count > 0) {
                // Instead of deleting, set quantity to 0 for all expired batches
                $update = $pdo->prepare("UPDATE stock_batches SET quantity_remaining = 0, notes = CONCAT(COALESCE(notes, ''), ' [EXPIRED - Bulk removed by admin on ', NOW(), ']') WHERE expiry_date < CURDATE() AND quantity_remaining > 0");
                $update->execute();
                
                // Log the bulk removal
                $current_user = get_logged_user();
                log_security_event(
                    $pdo, 
                    $current_user['user_id'], 
                    'BULK_EXPIRED_REMOVAL', 
                    "Removed all expired products - Total batches: $expired_count"
                );
                
                $pdo->commit();
                echo "<script>alert('All expired products removed successfully! ($expired_count batches removed)'); window.location.reload();</script>";
            } else {
                echo "<script>alert('No expired products found to remove.');</script>";
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
        }
    }
}

// Get expiring items
$expired = $pdo->query("
    SELECT sb.batch_id, p.product_name, sb.batch_number, sb.expiry_date, sb.quantity_remaining,
           DATEDIFF(sb.expiry_date, CURDATE()) as days_left
    FROM stock_batches sb
    INNER JOIN products p ON sb.product_id = p.product_id
    WHERE sb.expiry_date < CURDATE()
    AND sb.quantity_remaining > 0
    ORDER BY sb.expiry_date ASC
")->fetchAll();

$critical = $pdo->query("
    SELECT p.product_name, sb.batch_number, sb.expiry_date, sb.quantity_remaining,
           DATEDIFF(sb.expiry_date, CURDATE()) as days_left
    FROM stock_batches sb
    INNER JOIN products p ON sb.product_id = p.product_id
    WHERE sb.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND sb.quantity_remaining > 0
    ORDER BY sb.expiry_date ASC
")->fetchAll();

$warning = $pdo->query("
    SELECT p.product_name, sb.batch_number, sb.expiry_date, sb.quantity_remaining,
           DATEDIFF(sb.expiry_date, CURDATE()) as days_left
    FROM stock_batches sb
    INNER JOIN products p ON sb.product_id = p.product_id
    WHERE sb.expiry_date BETWEEN DATE_ADD(CURDATE(), INTERVAL 31 DAY) AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
    AND sb.quantity_remaining > 0
    ORDER BY sb.expiry_date ASC
")->fetchAll();
?>

<div class="stats-grid">
    <div class="stat-card danger">
        <div class="stat-label"><i class="fas fa-times-circle"></i> Expired</div>
        <div class="stat-value"><?php echo count($expired); ?></div>
    </div>
    <div class="stat-card warning">
        <div class="stat-label"><i class="fas fa-exclamation-triangle"></i> Critical (0-30 days)</div>
        <div class="stat-value"><?php echo count($critical); ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label"><i class="fas fa-info-circle"></i> Warning (31-90 days)</div>
        <div class="stat-value"><?php echo count($warning); ?></div>
    </div>
</div>

<!-- Expired Items -->
<?php if (!empty($expired)): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title" style="color: var(--danger-color);">
            <i class="fas fa-times-circle"></i> Expired Items
        </h2>
        <?php if (has_role('admin')): ?>
        <div class="card-actions">
            <form method="POST" style="display: inline;" onsubmit="return confirm('Remove ALL expired products? This action cannot be undone!')">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="remove_all_expired">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash-alt"></i> Remove All Expired
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Batch</th>
                    <th>Expiry Date</th>
                    <th>Days Overdue</th>
                    <th>Quantity</th>
                    <?php if (has_role('admin')): ?>
                    <th>Action</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($expired as $item): ?>
                <tr style="background: #fee2e2;">
                    <td><?php echo clean($item['product_name']); ?></td>
                    <td><?php echo clean($item['batch_number']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($item['expiry_date'])); ?></td>
                    <td><span class="badge badge-danger"><?php echo abs($item['days_left']); ?> days</span></td>
                    <td><?php echo $item['quantity_remaining']; ?></td>
                    <?php if (has_role('admin')): ?>
                    <td>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Remove this expired batch? This action cannot be undone!')">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="remove_expired_batch">
                            <input type="hidden" name="batch_id" value="<?php echo $item['batch_id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </form>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Critical Items -->
<?php if (!empty($critical)): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title" style="color: var(--warning-color);">
            <i class="fas fa-exclamation-triangle"></i> Critical - Expiring in 30 Days
        </h2>
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($critical as $item): ?>
                <tr style="background: #fef3c7;">
                    <td><?php echo clean($item['product_name']); ?></td>
                    <td><?php echo clean($item['batch_number']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($item['expiry_date'])); ?></td>
                    <td><span class="badge badge-warning"><?php echo $item['days_left']; ?> days</span></td>
                    <td><?php echo $item['quantity_remaining']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Warning Items -->
<?php if (!empty($warning)): ?>
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            <i class="fas fa-info-circle"></i> Warning - Expiring in 31-90 Days
        </h2>
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
                </tr>
            </thead>
            <tbody>
                <?php foreach ($warning as $item): ?>
                <tr>
                    <td><?php echo clean($item['product_name']); ?></td>
                    <td><?php echo clean($item['batch_number']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($item['expiry_date'])); ?></td>
                    <td><span class="badge badge-info"><?php echo $item['days_left']; ?> days</span></td>
                    <td><?php echo $item['quantity_remaining']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php if (empty($expired) && empty($critical) && empty($warning)): ?>
<div class="card">
    <div style="text-align: center; padding: 3rem;">
        <i class="fas fa-check-circle" style="font-size: 4rem; color: var(--success-color);"></i>
        <h2>All Clear!</h2>
        <p>No expiring or expired items found.</p>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
