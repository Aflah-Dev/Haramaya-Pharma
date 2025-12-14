<?php
/**
 * HARAMAYA PHARMA - Alert System
 * Global functions for stock and expiry warnings
 */

/**
 * Get low stock alert
 */
function get_low_stock_alerts($pdo) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.product_id,
                p.product_name,
                p.product_code,
                p.reorder_level,
                COALESCE(SUM(sb.quantity_remaining), 0) as current_stock,
                pc.category_name
            FROM products p
            LEFT JOIN stock_batches sb ON p.product_id = sb.product_id 
                AND sb.quantity_remaining > 0 
                AND sb.expiry_date >= CURDATE()
            LEFT JOIN product_categories pc ON p.category_id = pc.category_id
            WHERE p.is_active = 1
            GROUP BY p.product_id
            HAVING COALESCE(SUM(sb.quantity_remaining), 0) <= p.reorder_level
            ORDER BY (COALESCE(SUM(sb.quantity_remaining), 0) / NULLIF(p.reorder_level, 0)) ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Low stock alerts error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get expiry alerts
 */
function get_expiry_alerts($pdo) {
    try {
        // Expired items
        $expired = $pdo->query("
            SELECT 
                p.product_name,
                p.product_code,
                sb.batch_number,
                sb.expiry_date,
                sb.quantity_remaining,
                ABS(DATEDIFF(sb.expiry_date, CURDATE())) as days_overdue,
                'expired' as alert_type
            FROM stock_batches sb
            INNER JOIN products p ON sb.product_id = p.product_id
            WHERE sb.expiry_date < CURDATE()
            AND sb.quantity_remaining > 0
            ORDER BY sb.expiry_date ASC
        ")->fetchAll();
        
        // Critical (0-30 days)
        $critical = $pdo->query("
            SELECT 
                p.product_name,
                p.product_code,
                sb.batch_number,
                sb.expiry_date,
                sb.quantity_remaining,
                DATEDIFF(sb.expiry_date, CURDATE()) as days_left,
                'critical' as alert_type
            FROM stock_batches sb
            INNER JOIN products p ON sb.product_id = p.product_id
            WHERE sb.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
            AND sb.quantity_remaining > 0
            ORDER BY sb.expiry_date ASC
        ")->fetchAll();
        
        // Warning (31-90 days)
        $warning = $pdo->query("
            SELECT 
                p.product_name,
                p.product_code,
                sb.batch_number,
                sb.expiry_date,
                sb.quantity_remaining,
                DATEDIFF(sb.expiry_date, CURDATE()) as days_left,
                'warning' as alert_type
            FROM stock_batches sb
            INNER JOIN products p ON sb.product_id = p.product_id
            WHERE sb.expiry_date BETWEEN DATE_ADD(CURDATE(), INTERVAL 31 DAY) AND DATE_ADD(CURDATE(), INTERVAL 90 DAY)
            AND sb.quantity_remaining > 0
            ORDER BY sb.expiry_date ASC
        ")->fetchAll();
        
        return [
            'expired' => $expired,
            'critical' => $critical,
            'warning' => $warning,
            'total_expired' => count($expired),
            'total_critical' => count($critical),
            'total_warning' => count($warning)
        ];
    } catch (PDOException $e) {
        error_log("Expiry alerts error: " . $e->getMessage());
        return [
            'expired' => [],
            'critical' => [],
            'warning' => [],
            'total_expired' => 0,
            'total_critical' => 0,
            'total_warning' => 0
        ];
    }
}

/**
 * Get alert summary for dashboard
 */
function get_alert_summary($pdo) {
    $low_stock = get_low_stock_alerts($pdo);
    $expiry = get_expiry_alerts($pdo);
    
    return [
        'low_stock_count' => count($low_stock),
        'expired_count' => $expiry['total_expired'],
        'critical_expiry_count' => $expiry['total_critical'],
        'warning_expiry_count' => $expiry['total_warning'],
        'total_alerts' => count($low_stock) + $expiry['total_expired'] + $expiry['total_critical']
    ];
}

/**
 * Display alert notifications in header
 */
function display_alert_notifications($pdo) {
    $alerts = get_alert_summary($pdo);
    
    if ($alerts['total_alerts'] > 0) {
        echo '<div class="alert-notifications">';
        
        if ($alerts['expired_count'] > 0) {
            echo '<div class="alert-notification expired" onclick="window.location.href=\'/modules/stock/expiry-alerts.php\'">';
            echo '<i class="fas fa-times-circle"></i>';
            echo '<span>' . $alerts['expired_count'] . ' Expired Items</span>';
            echo '</div>';
        }
        
        if ($alerts['critical_expiry_count'] > 0) {
            echo '<div class="alert-notification critical" onclick="window.location.href=\'/modules/stock/expiry-alerts.php\'">';
            echo '<i class="fas fa-exclamation-triangle"></i>';
            echo '<span>' . $alerts['critical_expiry_count'] . ' Expiring Soon</span>';
            echo '</div>';
        }
        
        if ($alerts['low_stock_count'] > 0) {
            echo '<div class="alert-notification low-stock" onclick="window.location.href=\'/modules/stock/view.php?filter=low_stock\'">';
            echo '<i class="fas fa-boxes"></i>';
            echo '<span>' . $alerts['low_stock_count'] . ' Low Stock</span>';
            echo '</div>';
        }
        
        echo '</div>';
    }
}

/**
 * Get critical alerts for immediate attention
 */
function get_critical_alerts($pdo) {
    $alerts = [];
    
    // Expired items
    $expired = $pdo->query("
        SELECT COUNT(*) as count FROM stock_batches sb
        INNER JOIN products p ON sb.product_id = p.product_id
        WHERE sb.expiry_date < CURDATE() AND sb.quantity_remaining > 0
    ")->fetchColumn();
    
    if ($expired > 0) {
        $alerts[] = [
            'type' => 'expired',
            'count' => $expired,
            'message' => "$expired items have expired",
            'icon' => 'fas fa-times-circle',
            'color' => 'danger',
            'url' => '/modules/stock/expiry-alerts.php'
        ];
    }
    
    // Critical expiry (next 7 days)
    $critical = $pdo->query("
        SELECT COUNT(*) as count FROM stock_batches sb
        INNER JOIN products p ON sb.product_id = p.product_id
        WHERE sb.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND sb.quantity_remaining > 0
    ")->fetchColumn();
    
    if ($critical > 0) {
        $alerts[] = [
            'type' => 'critical_expiry',
            'count' => $critical,
            'message' => "$critical items expiring in 7 days",
            'icon' => 'fas fa-exclamation-triangle',
            'color' => 'warning',
            'url' => '/modules/stock/expiry-alerts.php'
        ];
    }
    
    // Out of stock
    $out_of_stock = $pdo->query("
        SELECT COUNT(DISTINCT p.product_id) as count
        FROM products p
        LEFT JOIN stock_batches sb ON p.product_id = sb.product_id 
            AND sb.quantity_remaining > 0 
            AND sb.expiry_date >= CURDATE()
        WHERE p.is_active = 1
        GROUP BY p.product_id
        HAVING COALESCE(SUM(sb.quantity_remaining), 0) = 0
    ")->fetchColumn();
    
    if ($out_of_stock > 0) {
        $alerts[] = [
            'type' => 'out_of_stock',
            'count' => $out_of_stock,
            'message' => "$out_of_stock products out of stock",
            'icon' => 'fas fa-exclamation-circle',
            'color' => 'danger',
            'url' => '/modules/stock/view.php?filter=out_of_stock'
        ];
    }
    
    return $alerts;
}
?>