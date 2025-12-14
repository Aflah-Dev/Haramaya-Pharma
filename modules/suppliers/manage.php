<?php
/*
 * HARAMAYA PHARMA - Suppliers Management
 */

$page_title = 'Manage Suppliers';
$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../templates/header.php';
require_role('admin');
?>
<style>
/* Suppliers Management Specific Styles */
.card {
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 30px;
    border: none;
    overflow: hidden;
    background: #ffffff;
}


/* header card style */
.card-header {
    background: linear-gradient(135deg, #4A6FA5 0%, #2C3E50 100%);
    color: white;
    padding: 20px 25px;
    border-bottom: none;
}

.card-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card-title:before {
    content: "🏥";
    font-size: 1.3rem;
}

.form-group {
    margin-bottom: 0;
    position: relative;
}

.form-label {
    font-weight: 600;
    color: #2C3E50;
    margin-bottom: 8px;
    font-size: 0.9rem;
    display: block;
}

.form-control {
    border: 2px solid #E8EDF2;
    border-radius: 8px;
    padding: 12px 15px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    background: #F8FAFC;
    width: 100%;
}

.form-control:focus {
    border-color: #4A6FA5;
    box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.15);
    background: white;
    outline: none;
}

input[type="text"]:focus,
input[type="email"]:focus,
textarea:focus {
    border-color: #4A6FA5;
}

textarea.form-control {
    min-height: 85px;
    resize: vertical;
    line-height: 1.5;
}

.btn {
    border-radius: 8px;
    padding: 12px 24px;
    font-weight: 600;
    font-size: 0.95rem;
    transition: all 0.3s ease;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.btn-primary {
    background: linear-gradient(135deg, #4A6FA5 0%, #3498DB 100%);
    color: white;
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.2);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
    background: linear-gradient(135deg, #3A5A95 0%, #2980B9 100%);
}

.btn-primary:active {
    transform: translateY(0);
}

.table-container {
    overflow-x: auto;
    padding: 20px;
}

.data-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    background: white;
}

.data-table thead {
    background: linear-gradient(135deg, #F8FAFC 0%, #E8EDF2 100%);
}

.data-table th {
    padding: 18px 15px;
    text-align: left;
    font-weight: 600;
    color: #2C3E50;
    border-bottom: 2px solid #E8EDF2;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    white-space: nowrap;
}

.data-table td {
    padding: 15px;
    border-bottom: 1px solid #F1F5F9;
    vertical-align: middle;
    background: white;
    transition: background 0.2s ease;
}

.data-table tbody tr:hover td {
    background: #F8FAFC;
}

.data-table tbody tr:last-child td {
    border-bottom: none;
}

.badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    display: inline-block;
}

.badge-info {
    background: rgba(52, 152, 219, 0.1);
    color: #2980B9;
    border: 1px solid rgba(52, 152, 219, 0.2);
}

input[type="checkbox"] {
    width: 18px;
    height: 18px;
    margin-right: 8px;
    cursor: pointer;
    accent-color: #4A6FA5;
}

input[type="checkbox"]:checked {
    background-color: #4A6FA5;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-header {
        padding: 15px 20px;
    }
    
    .data-table th,
    .data-table td {
        padding: 12px 10px;
        font-size: 0.85rem;
    }
    
    .btn {
        padding: 10px 18px;
        font-size: 0.9rem;
    }
}

/* Status indicator */
.data-table tbody tr td:last-child {
    position: relative;
}

.data-table tbody tr td:last-child:before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: #27AE60;
    border-radius: 0 4px 4px 0;
}

.data-table tbody tr input[type="checkbox"]:not(:checked) ~ * td:last-child:before {
    background: #E74C3C;
}

/* Form grid enhancement */
form[style*="grid-template-columns"] {
    padding: 25px;
    gap: 20px !important;
}

/* Required field indicator */
.form-control[required] {
    background-image: linear-gradient(45deg, transparent 95%, #E74C3C 95%);
    background-position: right 10px center;
    background-repeat: no-repeat;
    background-size: 8px 8px;
}

/* Placeholder styling */
::placeholder {
    color: #A0AEC0;
    opacity: 1;
}

/* Focus states for accessibility */
.form-control:focus-visible {
    outline: 2px solid #4A6FA5;
    outline-offset: 2px;
}
</style>

<?php
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