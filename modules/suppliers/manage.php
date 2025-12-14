<?php
/*
 * HARAMAYA PHARMA - Suppliers Management
 */

$page_title = 'Manage Suppliers';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../templates/header.php';
require_role('admin');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die('CSRF token validation failed');
    }
    
    $action = $_POST['action'] ?? '';
    
    // Add supplier
    if ($action === 'add') {
        $supplier_name = sanitize_input($_POST['supplier_name']);
        $contact_person = sanitize_input($_POST['contact_person']);
        $phone = sanitize_input($_POST['phone']);
        $email = sanitize_input($_POST['email']);
        $address = sanitize_input($_POST['address']);
        
        if ($supplier_name) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO suppliers (supplier_name, contact_person, phone, email, address)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$supplier_name, $contact_person, $phone, $email, $address]);
                
                log_security_event($pdo, $current_user['user_id'], 'SUPPLIER_ADDED', "Supplier: $supplier_name");
                echo "<script>alert('Supplier added successfully!'); window.location='manage.php';</script>";
            } catch (PDOException $e) {
                echo "<script>alert('Error: Supplier name already exists');</script>";
            }
        }
    }
    
    // Update supplier
    if ($action === 'update') {
        $supplier_id = (int)$_POST['supplier_id'];
        $supplier_name = sanitize_input($_POST['supplier_name']);
        $contact_person = sanitize_input($_POST['contact_person']);
        $phone = sanitize_input($_POST['phone']);
        $email = sanitize_input($_POST['email']);
        $address = sanitize_input($_POST['address']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = $pdo->prepare("
            UPDATE suppliers 
            SET supplier_name = ?, contact_person = ?, phone = ?, email = ?, address = ?, is_active = ?
            WHERE supplier_id = ?
        ");
        $stmt->execute([$supplier_name, $contact_person, $phone, $email, $address, $is_active, $supplier_id]);
        
        log_security_event($pdo, $current_user['user_id'], 'SUPPLIER_UPDATED', "Supplier ID: $supplier_id");
        echo "<script>alert('Supplier updated successfully!'); window.location='manage.php';</script>";
    }
}

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

<!-- Add Supplier Form -->
<div class="card">
    <div class="card-header">
        <h2 class="card-title">Add New Supplier</h2>
    </div>
    <form method="POST" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="add">
        
        <div class="form-group">
            <label class="form-label">Supplier Name *</label>
            <input type="text" name="supplier_name" class="form-control" required>
        </div>
        
        <div class="form-group">
            <label class="form-label">Contact Person</label>
            <input type="text" name="contact_person" class="form-control">
        </div>
        
        <div class="form-group">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" placeholder="+251-11-xxx-xxxx">
        </div>
        
        <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control">
        </div>
        
        <div class="form-group" style="grid-column: 1 / -1;">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="2" placeholder="Full address..."></textarea>
        </div>
        
        <div class="form-group" style="display: flex; align-items: flex-end;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Supplier
            </button>
        </div>
    </form>
</div>

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
                    <th>Status & Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suppliers as $supplier): ?>
                <tr>
                    <form method="POST" style="display: contents;">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="supplier_id" value="<?php echo $supplier['supplier_id']; ?>">
                        
                        <td>
                            <input type="text" name="supplier_name" 
                                   value="<?php echo clean($supplier['supplier_name']); ?>" 
                                   class="form-control" style="min-width: 150px;" required>
                        </td>
                        <td>
                            <input type="text" name="contact_person" 
                                   value="<?php echo clean($supplier['contact_person']); ?>" 
                                   class="form-control" style="min-width: 120px;">
                        </td>
                        <td>
                            <input type="text" name="phone" 
                                   value="<?php echo clean($supplier['phone']); ?>" 
                                   class="form-control" style="min-width: 120px;">
                        </td>
                        <td>
                            <input type="email" name="email" 
                                   value="<?php echo clean($supplier['email']); ?>" 
                                   class="form-control" style="min-width: 150px;">
                        </td>
                        <td>
                            <textarea name="address" class="form-control" rows="1" 
                                      style="min-width: 150px; resize: vertical;"><?php echo clean($supplier['address']); ?></textarea>
                        </td>
                        <td>
                            <span class="badge badge-info">
                                <?php echo $supplier['total_batches']; ?> batches
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: center;">
                                <strong><?php echo number_format($supplier['total_supplied'] ?? 0); ?> units</strong>
                                <div style="display: flex; gap: 0.5rem; align-items: center;">
                                    <label style="font-size: 0.875rem;">
                                        <input type="checkbox" name="is_active" <?php echo $supplier['is_active'] ? 'checked' : ''; ?>>
                                        Active
                                    </label>
                                    <button type="submit" class="btn btn-primary" style="padding: 0.25rem 0.5rem; font-size: 0.875rem;">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                </div>
                            </div>
                        </td>
                    </form>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../templates/footer.php'; ?>
