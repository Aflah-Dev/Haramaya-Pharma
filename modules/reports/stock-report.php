<?php
/**
 * HARAMAYA PHARMA - Stock Report
 */

$page_title = 'Stock Report';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../templates/header.php';
require_role(['admin', 'pharmacist']);

// Fetch stock summary by product
$stmt = $pdo->query("
    SELECT 
        p.product_name,
        p.product_code,
        pc.category_name,
        COUNT(sb.batch_id) as batch_count,
        SUM(sb.quantity_remaining) as total_quantity,
        MIN(sb.expiry_date) as nearest_expiry,
        p.reorder_level,
        p.unit_price
    FROM products p
    LEFT JOIN product_categories pc ON p.category_id = pc.category_id
    LEFT JOIN stock_batches sb ON p.product_id = sb.product_id AND sb.quantity_remaining > 0
    GROUP BY p.product_id
    ORDER BY p.product_name
");
$stock_items = $stmt->fetchAll();

// Calculate statistics
$total_products = count($stock_items);
$low_stock_count = 0;
$out_of_stock_count = 0;
$total_value = 0;

foreach ($stock_items as $item) {
    $qty = $item['total_quantity'] ?? 0;
    if ($qty == 0) $out_of_stock_count++;
    elseif ($qty <= $item['reorder_level']) $low_stock_count++;
    $total_value += $qty * $item['unit_price'];
}
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Stock Report</h2>
    </div>
    
    <div class="card-body">
        <!-- Summary Cards -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
            <div class="stat-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 1.5rem; border-radius: 8px;">
                <div style="font-size: 0.875rem; opacity: 0.9;">Total Products</div>
                <div style="font-size: 2rem; font-weight: bold; margin-top: 0.5rem;">
                    <?php echo $total_products; ?>
                </div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 1.5rem; border-radius: 8px;">
                <div style="font-size: 0.875rem; opacity: 0.9;">Low Stock Items</div>
                <div style="font-size: 2rem; font-weight: bold; margin-top: 0.5rem;">
                    <?php echo $low_stock_count; ?>
                </div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 1.5rem; border-radius: 8px;">
                <div style="font-size: 0.875rem; opacity: 0.9;">Out of Stock</div>
                <div style="font-size: 2rem; font-weight: bold; margin-top: 0.5rem;">
                    <?php echo $out_of_stock_count; ?>
                </div>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 1.5rem; border-radius: 8px;">
                <div style="font-size: 0.875rem; opacity: 0.9;">Stock Value</div>
                <div style="font-size: 2rem; font-weight: bold; margin-top: 0.5rem;">
                    ETB <?php echo number_format($total_value, 2); ?>
                </div>
            </div>
        </div>

        <!-- Stock Table -->
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product Code</th>
                        <th>Product Name</th>
                        <th>Category</th>
                        <th>Batches</th>
                        <th>Quantity</th>
                        <th>Reorder Level</th>
                        <th>Status</th>
                        <th>Nearest Expiry</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($stock_items as $item): ?>
                    <?php 
                        $qty = $item['total_quantity'] ?? 0;
                        if ($qty == 0) {
                            $status = '<span class="badge badge-danger">Out of Stock</span>';
                        } elseif ($qty <= $item['reorder_level']) {
                            $status = '<span class="badge badge-warning">Low Stock</span>';
                        } else {
                            $status = '<span class="badge badge-success">In Stock</span>';
                        }
                    ?>
                    <tr>
                        <td><code><?php echo clean($item['product_code']); ?></code></td>
                        <td><strong><?php echo clean($item['product_name']); ?></strong></td>
                        <td><?php echo clean($item['category_name']); ?></td>
                        <td><?php echo $item['batch_count']; ?></td>
                        <td><strong><?php echo $qty; ?></strong></td>
                        <td><?php echo $item['reorder_level']; ?></td>
                        <td><?php echo $status; ?></td>
                        <td>
                            <?php if ($item['nearest_expiry']): ?>
                                <?php echo date('M d, Y', strtotime($item['nearest_expiry'])); ?>
                            <?php else: ?>
                                <span style="color: var(--text-secondary);">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
