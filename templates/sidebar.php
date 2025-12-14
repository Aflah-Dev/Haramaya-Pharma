<?php
 
// HARAMAYA PHARMA - Sidebar Navigation
$current_page = basename($_SERVER['PHP_SELF']);
// Determine base path relative to current module
$base = '../..';


?>
<!-- Mobile sidebar overlay -->
<div class="sidebar-overlay"></div>

<aside class="sidebar">
    <div class="sidebar-header">
        <img src="<?php echo $base; ?>/assets/images/image.jpg" alt="Haramaya Pharma" class="logo-img" 
             style="width: 60px; height: 60px; object-fit: contain; margin-bottom: 0.5rem; border-radius: 8px;"
             onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
        <i class="fas fa-pills" style="font-size: 3rem; display: none; margin-bottom: 0.5rem; color: #28a745;"></i>
        <h2>Haramaya Pharma</h2>
    </div>
    
    <nav class="sidebar-nav">
        <!-- Dashboard Section -->
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <a href="<?php echo $base; ?>/modules/dashboard/index.php" 
               class="nav-link <?php echo $current_page === 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i>
                <span>Dashboard</span>
            </a>

        </div>
        
        <!-- Sales Section -->
        <div class="nav-section">
            <div class="nav-section-title">Sales</div>
            <a href="<?php echo $base; ?>/modules/sales/pos.php" 
               class="nav-link <?php echo $current_page === 'pos.php' ? 'active' : ''; ?>">
                <i class="fas fa-cash-register"></i>
                <span>Point of Sale</span>
            </a>
            <a href="<?php echo $base; ?>/modules/sales/history.php" 
               class="nav-link <?php echo $current_page === 'history.php' ? 'active' : ''; ?>">
                <i class="fas fa-history"></i>
                <span>Sales History</span>
            </a>
        </div>
         
         
        <!-- Stock Management Section -->
        <div class="nav-section">
            <div class="nav-section-title">Inventory</div>
            <a href="<?php echo $base; ?>/modules/stock/view.php" 
               class="nav-link <?php echo $current_page === 'view.php' ? 'active' : ''; ?>">
                <i class="fas fa-boxes"></i>
                <span>View Stock</span>
            </a>
            <?php if (has_role(['admin', 'pharmacist'])): ?>
            <a href="<?php echo $base; ?>/modules/stock/add.php" 
               class="nav-link <?php echo $current_page === 'add.php' ? 'active' : ''; ?>">
                <i class="fas fa-plus-circle"></i>
                <span>Add Stock</span>
            </a>
            <?php endif; ?>
            <a href="<?php echo $base; ?>/modules/stock/expiry-alerts.php" 
               class="nav-link <?php echo $current_page === 'expiry-alerts.php' ? 'active' : ''; ?>">
                <i class="fas fa-exclamation-triangle"></i>
                <span>Expiry Alerts</span>
            </a>
        </div>
        
        <!-- Products Section -->
        <?php if (has_role(['admin', 'pharmacist'])): ?>
        <div class="nav-section">
            <div class="nav-section-title">Products</div>
            <a href="<?php echo $base; ?>/modules/products/manage.php" 
               class="nav-link <?php echo $current_page === 'manage.php' ? 'active' : ''; ?>">
                <i class="fas fa-capsules"></i>
                <span>Manage Products</span>
            </a>
            <a href="<?php echo $base; ?>/modules/products/categories.php" 
               class="nav-link <?php echo $current_page === 'categories.php' ? 'active' : ''; ?>">
                <i class="fas fa-tags"></i>
                <span>Categories</span>
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Reports Section -->
        <?php if (has_role(['admin', 'pharmacist'])): ?>
        <div class="nav-section">
            <div class="nav-section-title">Reports</div>
            <a href="<?php echo $base; ?>/modules/reports/sales-report.php" 
               class="nav-link">
                <i class="fas fa-chart-line"></i>
                <span>Sales Report</span>
            </a>
            <a href="<?php echo $base; ?>/modules/reports/stock-report.php" 
               class="nav-link">
                <i class="fas fa-warehouse"></i>
                <span>Stock Report</span>
            </a>
            <a href="<?php echo $base; ?>/modules/reports/expiry-report.php" 
               class="nav-link">
                <i class="fas fa-calendar-times"></i>
                <span>Expiry Report</span>
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Admin Section -->
        <?php if (has_role('admin')): ?>
        <div class="nav-section">
            <div class="nav-section-title">Administration</div>
            <a href="<?php echo $base; ?>/modules/management/index.php" 
               class="nav-link <?php echo $current_page === 'index.php' && strpos($_SERVER['REQUEST_URI'], '/management/') !== false ? 'active' : ''; ?>">
                <i class="fas fa-cogs"></i>
                <span>Management Center</span>
            </a>
            <a href="<?php echo $base; ?>/modules/users/manage.php" 
               class="nav-link">
                <i class="fas fa-users"></i>
                <span>User Management</span>
            </a>
            <a href="<?php echo $base; ?>/modules/suppliers/manage.php" 
               class="nav-link">
                <i class="fas fa-truck"></i>
                <span>Suppliers</span>
            </a>
        </div>
        <?php endif; ?>
    </nav>
</aside>
