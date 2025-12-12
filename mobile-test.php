<?php
/**
 * HARAMAYA PHARMA - Mobile Responsiveness Test Page
 */

$pdo = require __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';

secure_session_start();

// Redirect if not logged in
if (!is_logged_in()) {
    header('Location: modules/auth/login.php');
    exit;
}

$page_title = 'Mobile Test';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobile Test - Haramaya Pharma</title>
    <link rel="icon" type="image/jpeg" href="assets/images/favicon.jpg">
    <link rel="stylesheet" href="assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Mobile sidebar overlay -->
        <div class="sidebar-overlay"></div>

        <aside class="sidebar">
            <div class="sidebar-header">
                <img src="assets/images/image.jpg" alt="Haramaya Pharma" class="logo-img" 
                     style="width: 60px; height: 60px; object-fit: contain; margin-bottom: 0.5rem; border-radius: 8px;"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                <i class="fas fa-pills" style="font-size: 3rem; display: none; margin-bottom: 0.5rem; color: #28a745;"></i>
                <h2>Haramaya Pharma</h2>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <a href="modules/dashboard/index.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Sales</div>
                    <a href="modules/sales/pos.php" class="nav-link">
                        <i class="fas fa-cash-register"></i>
                        <span>Point of Sale</span>
                    </a>
                    <a href="modules/sales/history.php" class="nav-link">
                        <i class="fas fa-history"></i>
                        <span>Sales History</span>
                    </a>
                </div>
                
                <div class="nav-section">
                    <div class="nav-section-title">Inventory</div>
                    <a href="modules/stock/view.php" class="nav-link">
                        <i class="fas fa-boxes"></i>
                        <span>View Stock</span>
                    </a>
                    <a href="modules/stock/add.php" class="nav-link">
                        <i class="fas fa-plus-circle"></i>
                        <span>Add Stock</span>
                    </a>
                </div>
            </nav>
        </aside>
        
        <div class="main-content">
            <header class="top-header">
                <div class="header-title">
                    <button class="mobile-menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1>Mobile Responsiveness Test</h1>
                </div>
                <div class="header-actions">
                    <div class="user-info">
                        <div class="user-avatar">A</div>
                        <div>
                            <div style="font-weight: 600;">Admin User</div>
                            <div style="font-size: 0.875rem; color: var(--text-secondary);">Administrator</div>
                        </div>
                    </div>
                    <a href="modules/auth/logout.php" class="btn btn-secondary">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </header>
            
            <div class="content-area">
                <!-- Stats Grid -->
                <div class="stats-grid">
                    <div class="stat-card success">
                        <div class="stat-label">
                            <i class="fas fa-pills"></i>
                            Total Products
                        </div>
                        <div class="stat-value">1,234</div>
                    </div>
                    <div class="stat-card warning">
                        <div class="stat-label">
                            <i class="fas fa-exclamation-triangle"></i>
                            Low Stock
                        </div>
                        <div class="stat-value">23</div>
                    </div>
                    <div class="stat-card danger">
                        <div class="stat-label">
                            <i class="fas fa-calendar-times"></i>
                            Expired Items
                        </div>
                        <div class="stat-value">5</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">
                            <i class="fas fa-chart-line"></i>
                            Today's Sales
                        </div>
                        <div class="stat-value">ETB 15,420</div>
                    </div>
                </div>

                <!-- Sample Form -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Sample Form</h3>
                        <div class="card-actions">
                            <button class="btn btn-primary">
                                <i class="fas fa-plus"></i> Add New
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <form>
                            <div class="form-group">
                                <label class="form-label">Product Name</label>
                                <input type="text" class="form-control" placeholder="Enter product name">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Category</label>
                                <select class="form-control">
                                    <option>Select category</option>
                                    <option>Antibiotics</option>
                                    <option>Pain Relief</option>
                                    <option>Vitamins</option>
                                </select>
                            </div>
                            <div class="form-actions">
                                <button type="submit" class="btn btn-primary">Save</button>
                                <button type="button" class="btn btn-secondary">Cancel</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sample Table -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Sample Data Table</h3>
                    </div>
                    <div class="card-body">
                        <div class="responsive-table">
                            <table class="data-table stack-table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Category</th>
                                        <th>Stock</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td data-label="Product">Paracetamol 500mg</td>
                                        <td data-label="Category">Pain Relief</td>
                                        <td data-label="Stock">450</td>
                                        <td data-label="Price">ETB 2.50</td>
                                        <td data-label="Status"><span class="badge badge-success">In Stock</span></td>
                                        <td data-label="Actions">
                                            <button class="btn btn-sm btn-primary">Edit</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td data-label="Product">Amoxicillin 250mg</td>
                                        <td data-label="Category">Antibiotics</td>
                                        <td data-label="Stock">180</td>
                                        <td data-label="Price">ETB 15.00</td>
                                        <td data-label="Status"><span class="badge badge-warning">Low Stock</span></td>
                                        <td data-label="Actions">
                                            <button class="btn btn-sm btn-primary">Edit</button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td data-label="Product">Vitamin C 100mg</td>
                                        <td data-label="Category">Vitamins</td>
                                        <td data-label="Stock">950</td>
                                        <td data-label="Price">ETB 5.00</td>
                                        <td data-label="Status"><span class="badge badge-success">In Stock</span></td>
                                        <td data-label="Actions">
                                            <button class="btn btn-sm btn-primary">Edit</button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Mobile Instructions -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">📱 Mobile Testing Instructions</h3>
                    </div>
                    <div class="card-body">
                        <h4>Test on Different Devices:</h4>
                        <ul>
                            <li><strong>Desktop:</strong> Full sidebar visible, normal layout</li>
                            <li><strong>Tablet:</strong> Responsive grid, touch-friendly buttons</li>
                            <li><strong>Mobile:</strong> Hamburger menu, stacked layout, touch optimized</li>
                        </ul>
                        
                        <h4>Features to Test:</h4>
                        <ul>
                            <li>📱 Tap hamburger menu (☰) to open/close sidebar</li>
                            <li>📊 Stats cards stack vertically on mobile</li>
                            <li>📋 Table becomes card-style on small screens</li>
                            <li>🔘 Buttons are touch-friendly (44px minimum)</li>
                            <li>📝 Forms stack vertically on mobile</li>
                            <li>🎯 All text is readable without zooming</li>
                        </ul>

                        <h4>Browser Developer Tools:</h4>
                        <p>Press <kbd>F12</kbd> → Click device icon → Test different screen sizes</p>
                        
                        <div class="alert-notifications">
                            <div class="alert-notification low-stock">
                                <i class="fas fa-info-circle"></i>
                                Responsive design active!
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <footer class="main-footer">
                <div class="footer-content">
                    <div class="footer-left">
                        <p>Haramaya Pharma Management System</p>
                        <p>Professional Pharmacy Solutions</p>
                    </div>
                    <div class="footer-right">
                        <p>Mobile Responsive Design</p>
                        <p>Optimized for all devices</p>
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <script src="assets/js/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>