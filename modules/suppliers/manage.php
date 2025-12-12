<?php
/**
 * HARAMAYA PHARMA - Suppliers Management
 */

$page_title = 'Manage Suppliers';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../templates/header.php';
require_role('admin');

// Fetch suppliers with stock count
$stmt = $pdo->query("
    SELECT 
        s.*,
        COUNT(DISTINCT sb.batch_id) as total_batches,
        SUM(sb.quantity_received) as total_supplied
    FROM suppliers s
    LEFT JOIN stock_batches sb ON s.supplier_id = sb.supplier_id
    GROUP BY s.supplier_id
    ORDER BY s.supplier_name
");
$suppliers = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Suppliers</h2>
    </div>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Supplier Name</th>
                    <th>Contact Person</th>
                    <th>Phone</th>
                    <th>Email</th>
                    <th>Address</th>
                    <th>Total Batches</th>
                    <th>Total Supplied</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $supplier): ?>
                <tr>
                    <td><strong><?php echo clean($supplier['supplier_name']); ?></strong></td>
                    <td><?php echo clean($supplier['contact_person']); ?></td>
                    <td><?php echo clean($supplier['phone']); ?></td>
                    <td><?php echo clean($supplier['email']); ?></td>
                    <td><?php echo clean($supplier['address']); ?></td>
                    <td>
                        <span class="badge badge-info">
                            <?php echo $supplier['total_batches']; ?> batches
                        </span>
                    </td>
                    <td><strong><?php echo number_format($supplier['total_supplied'] ?? 0); ?></strong> units</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
