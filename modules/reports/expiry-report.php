<?php

//HARAMAYA PHARMA - Expiry Report
 

$page_title = 'Expiry Report';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../templates/header.php';
require_role(['admin', 'pharmacist']);

// Fetch expiring stock grouped by time period
$stmt = $pdo->query("
    SELECT 
        p.product_name,
        p.product_code,
        sb.batch_number,
        sb.quantity_remaining,
        sb.expiry_date,
        p.unit_price,
        DATEDIFF(sb.expiry_date, CURDATE()) as days_to_expiry,
        CASE 
            WHEN DATEDIFF(sb.expiry_date, CURDATE()) < 0 THEN 'expired'
            WHEN DATEDIFF(sb.expiry_date, CURDATE()) <= 30 THEN 'critical'
            WHEN DATEDIFF(sb.expiry_date, CURDATE()) <= 90 THEN 'warning'
            ELSE 'normal'
        END as status
    FROM stock_batches sb
    JOIN products p ON sb.product_id = p.product_id
    WHERE sb.quantity_remaining > 0
    ORDER BY sb.expiry_date ASC
");
$batches = $stmt->fetchAll();

// Group by status
$expired = array_filter($batches, fn($b) => $b['status'] === 'expired');
$critical = array_filter($batches, fn($b) => $b['status'] === 'critical');
$warning = array_filter($batches, fn($b) => $b['status'] === 'warning');

// Calculate potential losses
$expired_value = array_sum(array_map(fn($b) => $b['quantity_remaining'] * $b['unit_price'], $expired));
$critical_value = array_sum(array_map(fn($b) => $b['quantity_remaining'] * $b['unit_price'], $critical));
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Expiry Report</h2>
    </div>
    
    <div class="card-body">
        <!-- Summary Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 1.5rem; border-radius: 8px;">
                <div style="font-size: 0.875rem; opacity: 0.9;">Expired Batches</div>
                <div style="font-size: 2rem; font-weight: bold; margin-top: 0.5rem;">
                    <?php echo count($expired); ?>
                </div>
                <div style="font-size: 0.875rem; opacity: 0.9; margin-top: 0.5rem;">
                    Value: ETB <?php echo number_format($expired_value, 2); ?>
                </div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 1.5rem; border-radius: 8px;">
                <div style="font-size: 0.875rem; opacity: 0.9;">Critical (≤30 days)</div>
                <div style="font-size: 2rem; font-weight: bold; margin-top: 0.5rem;">
                    <?php echo count($critical); ?>
                </div>
                <div style="font-size: 0.875rem; opacity: 0.9; margin-top: 0.5rem;">
                    Value: ETB <?php echo number_format($critical_value, 2); ?>
                </div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); color: #333; padding: 1.5rem; border-radius: 8px;">
                <div style="font-size: 0.875rem; opacity: 0.8;">Warning (≤90 days)</div>
                <div style="font-size: 2rem; font-weight: bold; margin-top: 0.5rem;">
                    <?php echo count($warning); ?>
                </div>
            </div>
        </div>

        <!-- Expiring Stock Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Batch Number</th>
                        <th>Quantity</th>
                        <th>Expiry Date</th>
                        <th>Days Left</th>
                        <th>Status</th>
                        <th>Value at Risk</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($batches as $batch): ?>
                    <?php
                        $days = $batch['days_to_expiry'];
                        $value = $batch['quantity_remaining'] * $batch['unit_price'];
                        
                        if ($batch['status'] === 'expired') {
                            $badge = '<span class="badge badge-danger">EXPIRED</span>';
                            $row_style = 'background-color: #fee2e2;';
                        } elseif ($batch['status'] === 'critical') {
                            $badge = '<span class="badge badge-danger">CRITICAL</span>';
                            $row_style = 'background-color: #fef3c7;';
                        } elseif ($batch['status'] === 'warning') {
                            $badge = '<span class="badge badge-warning">WARNING</span>';
                            $row_style = '';
                        } else {
                            $badge = '<span class="badge badge-success">OK</span>';
                            $row_style = '';
                        }
                    ?>
                    <tr style="<?php echo $row_style; ?>">
                        <td>
                            <strong><?php echo clean($batch['product_name']); ?></strong><br>
                            <small style="color: var(--text-secondary);"><?php echo clean($batch['product_code']); ?></small>
                        </td>
                        <td><code><?php echo clean($batch['batch_number']); ?></code></td>
                        <td><strong><?php echo $batch['quantity_remaining']; ?></strong></td>
                        <td><?php echo date('M d, Y', strtotime($batch['expiry_date'])); ?></td>
                        <td>
                            <?php if ($days < 0): ?>
                                <strong style="color: var(--danger-color);"><?php echo abs($days); ?> days ago</strong>
                            <?php else: ?>
                                <strong><?php echo $days; ?> days</strong>
                            <?php endif; ?>
                        </td>
                        <td><?php echo $badge; ?></td>
                        <td><strong>ETB <?php echo number_format($value, 2); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
