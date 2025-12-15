<?php
 
// HARAMAYA PHARMA - Common Header Template

if (session_status() === PHP_SESSION_NONE) {
    require_once __DIR__ . '/../includes/security.php';
    secure_session_start();
}

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/alerts.php';
require_login();

$current_user = get_logged_user();
$page_title = $page_title ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo clean($page_title); ?> - Haramaya Pharma</title>
    <link rel="icon" type="image/jpeg" href="../../assets/images/favicon.jpg">
    <link rel="stylesheet" href="../../assets/css/main.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <?php include __DIR__ . '/sidebar.php'; ?>
        
        <div class="main-content">
            <header class="top-header">
                <div class="header-title">
                    <button class="mobile-menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1><?php echo clean($page_title); ?></h1>
                </div>
                <div class="header-actions">
                    <div class="user-info">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($current_user['full_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <div style="font-weight: 600;"><?php echo clean($current_user['full_name']); ?></div>
                            <div style="font-size: 0.875rem; color: var(--text-secondary);">
                                <?php echo ucfirst(clean($current_user['role'])); ?>
                            </div>
                        </div>
                    </div>
                    <a href="../auth/logout.php" class="btn btn-secondary">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </header>
            
            <div class="content-area">
