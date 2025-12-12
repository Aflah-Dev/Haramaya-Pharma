<?php
/**
 * HARAMAYA PHARMA - View Stock with FEFO Display
 * Shows all products with their batches and expiry dates
 */

$page_title = 'Stock Management';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/alerts.php';
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

// Get low stock alerts
$low_stock_items = get_low_stock_alerts($pdo);

// Fetch stock data with FEFO ordering (oldest expiry first)
$query = "
    SELECT 
        p.product_id,
        p.product_code,
        p.product_name,
        p.generic_name,
        p.strength,
        p.dosage_form,
        pc.category_name,
        sb.batch_id,
        sb.batch_number,
        sb.quantity_remaining,
        sb.expiry_date,
        sb.unit_cost,
        s.supplier_name,
        DATEDIFF(sb.expiry_date, CURDATE()) as days_to_expiry,
        CASE 
            WHEN DATEDIFF(sb.expiry_date, CURDATE()) < 0 THEN 'expired'
            WHEN DATEDIFF(sb.expiry_date, CURDATE()) <= 30 THEN 'critical'
            WHEN DATEDIFF(sb.expiry_date, CURDATE()) <= 90 THEN 'warning'
            ELSE 'good'
        END as expiry_status
    FROM stock_batches sb
    INNER JOIN products p ON sb.product_id = p.product_id
    LEFT JOIN product_categories pc ON p.category_id = pc.category_id
    LEFT JOIN suppliers s ON sb.supplier_id = s.supplier_id
    WHERE sb.quantity_remaining > 0
    ORDER BY p.product_name ASC, sb.expiry_date ASC
";

try {
    $stmt = $pdo->query($query);
    $stock_items = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_message = "Error fetching stock data: " . $e->getMessage();
    $stock_items = [];
}

// Calculate summary statistics
$total_products = count(array_unique(array_column($stock_items, 'product_id')));
$total_batches = count($stock_items);
$expiring_soon = count(array_filter($stock_items, function($item) {
    return $item['expiry_status'] === 'critical' || $item['expiry_status'] === 'warning';
}));
$expired = count(array_filter($stock_items, function($item) {
    return $item['expiry_status'] === 'expired';
}));
?>

<!-- Statistics Cards -->
<div class="stats-grid">
    <div class="stat-card success">
        <div class="stat-label">Total Products</div>
        <div class="stat-value"><?php echo $total_products; ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Batches</div>
        <div class="stat-value"><?php echo $total_batches; ?></div>
    </div>
    <div class="stat-card warning">
        <div class="stat-label">Expiring Soon</div>
        <div class="stat-value"><?php echo $expiring_soon; ?></div>
    </div>
    <div class="stat-card danger">
        <div class="stat-label">Expired</div>
        <div class="stat-value"><?php echo $expired; ?></div>
    </div>
</div>

<!-- Stock Table -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Current Stock (FEFO Order)</h2>
        <div class="card-actions">
            <?php if (has_role(['admin', 'pharmacist'])): ?>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Stock
            </a>
            <?php endif; ?>
            <?php if (has_role('admin') && $expired > 0): ?>
            <form method="POST" style="display: inline;" onsubmit="return confirm('Remove ALL expired products? This action cannot be undone!')">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="remove_all_expired">
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash-alt"></i> Remove All Expired (<?php echo $expired; ?>)
                </button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Search Box -->
    <div class="search-box">
        <i class="fas fa-search search-icon"></i>
        <input type="text" 
               id="stockSearch" 
               class="form-control" 
               placeholder="Search by product name, batch number, or code...">
    </div>
    
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger"><?php echo clean($error_message); ?></div>
    <?php endif; ?>
    
    <div class="table-container">
        <table class="data-table" id="stockTable">
            <thead>
                <tr>
                    <th>Product Code</th>
                    <th>Product Name</th>
                    <th>Batch Number</th>
                    <th>Quantity</th>
                    <th>Expiry Date</th>
                    <th>Days Left</th>
                    <th>Status</th>
                    <th>Supplier</th>
                    <th>Unit Cost</th>
                    <?php if (has_role('admin')): ?>
                    <th>Action</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stock_items as $item): ?>
                <tr class="<?php echo $item['expiry_status'] === 'expired' ? 'expired-row' : ''; ?>">
                    <td><?php echo clean($item['product_code']); ?></td>
                    <td>
                        <strong><?php echo clean($item['product_name']); ?></strong><br>
                        <small><?php echo clean($item['generic_name']); ?> 
                               <?php echo clean($item['strength']); ?></small>
                    </td>
                    <td><?php echo clean($item['batch_number']); ?></td>
                    <td><?php echo clean($item['quantity_remaining']); ?></td>
                    <td><?php echo date('M d, Y', strtotime($item['expiry_date'])); ?></td>
                    <td>
                        <?php if ($item['expiry_status'] === 'expired'): ?>
                            <span style="color: var(--danger-color); font-weight: bold;">
                                <?php echo abs($item['days_to_expiry']); ?> days overdue
                            </span>
                        <?php else: ?>
                            <?php echo $item['days_to_expiry']; ?> days
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php
                        $badge_class = [
                            'expired' => 'badge-danger',
                            'critical' => 'badge-danger',
                            'warning' => 'badge-warning',
                            'good' => 'badge-success'
                        ][$item['expiry_status']];
                        
                        $badge_text = [
                            'expired' => 'EXPIRED',
                            'critical' => 'Critical',
                            'warning' => 'Warning',
                            'good' => 'Good'
                        ][$item['expiry_status']];
                        ?>
                        <span class="badge <?php echo $badge_class; ?>">
                            <?php if ($item['expiry_status'] === 'expired'): ?>
                                <i class="fas fa-times-circle"></i> 
                            <?php endif; ?>
                            <?php echo $badge_text; ?>
                        </span>
                    </td>
                    <td><?php echo clean($item['supplier_name'] ?? 'N/A'); ?></td>
                    <td>ETB <?php echo number_format($item['unit_cost'], 2); ?></td>
                    <?php if (has_role('admin')): ?>
                    <td>
                        <?php if ($item['expiry_status'] === 'expired'): ?>
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Remove this expired batch? This action cannot be undone!')">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="action" value="remove_expired_batch">
                            <input type="hidden" name="batch_id" value="<?php echo $item['batch_id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </form>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.expired-row {
    background-color: #fee2e2 !important;
    border-left: 4px solid var(--danger-color);
}

.expired-row:hover {
    background-color: #fecaca !important;
}

.card-actions {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.card-actions form {
    margin: 0;
}
</style>

<script>
// Stock search functionality
document.getElementById('stockSearch').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#stockTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});
</script>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
