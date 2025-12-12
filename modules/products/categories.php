<?php
/**
 * HARAMAYA PHARMA - Product Categories Management
 */

$page_title = 'Product Categories';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../templates/header.php';
require_role(['admin', 'pharmacist']);

// Fetch categories
$stmt = $pdo->query("
    SELECT c.*, COUNT(p.product_id) as product_count
    FROM product_categories c
    LEFT JOIN products p ON c.category_id = p.category_id
    GROUP BY c.category_id
    ORDER BY c.category_name
");
$categories = $stmt->fetchAll();
?>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Product Categories</h2>
    </div>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Category Name</th>
                    <th>Description</th>
                    <th>Products</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                <tr>
                    <td><strong><?php echo clean($category['category_name']); ?></strong></td>
                    <td><?php echo clean($category['description'] ?? '-'); ?></td>
                    <td>
                        <span class="badge badge-info">
                            <?php echo $category['product_count']; ?> products
                        </span>
                    </td>
                    <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
