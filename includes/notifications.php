<?php
/**
 * HARAMAYA PHARMA - Notification System
 * Email and system notifications for alerts
 */

/**
 * Send alert notification
 */
function send_alert_notification($type, $message, $recipients = []) {
    // Log the notification
    error_log("ALERT NOTIFICATION [$type]: $message");
    
    // In a real system, you would integrate with:
    // - Email service (PHPMailer, SendGrid, etc.)
    // - SMS service (Twilio, etc.)
    // - Push notifications
    // - Slack/Teams webhooks
    
    return true;
}

/**
 * Check and send daily alert summary
 */
function send_daily_alert_summary($pdo) {
    $alert_summary = get_alert_summary($pdo);
    
    if ($alert_summary['total_alerts'] > 0) {
        $message = "Daily Alert Summary:\n";
        $message .= "- Expired Items: {$alert_summary['expired_count']}\n";
        $message .= "- Expiring Soon: {$alert_summary['critical_expiry_count']}\n";
        $message .= "- Low Stock: {$alert_summary['low_stock_count']}\n";
        $message .= "Total Alerts: {$alert_summary['total_alerts']}";
        
        send_alert_notification('DAILY_SUMMARY', $message, ['admin@haramayapharma.com']);
    }
}

/**
 * Send critical alert immediately
 */
function send_critical_alert($pdo, $alert_type, $details) {
    $message = "CRITICAL ALERT: $alert_type\n";
    $message .= "Details: $details\n";
    $message .= "Time: " . date('Y-m-d H:i:s');
    
    send_alert_notification('CRITICAL', $message, [
        'admin@haramayapharma.com',
        'pharmacist@haramayapharma.com'
    ]);
    
    
    // Log to activity log
    log_security_event($pdo, null, 'CRITICAL_ALERT_SENT', $message);
}

/**
 * Check for new critical alerts and send notifications
 */
function check_and_notify_critical_alerts($pdo) {
    $critical_alerts = get_critical_alerts($pdo);
    
    foreach ($critical_alerts as $alert) {
        // Check if we've already sent this alert today
        $today = date('Y-m-d');
        $alert_key = $alert['type'] . '_' . $today;
        
        // In a real system, you'd store sent alerts in database
        // For now, we'll just log them
        send_critical_alert($pdo, $alert['type'], $alert['message']);
    }
}

/**
 * Format alert for display
 */
function format_alert_message($alert_type, $count, $details = '') {
    $messages = [
        'expired' => "⚠️ $count items have expired and need immediate attention",
        'critical_expiry' => "🕐 $count items are expiring within 7 days",
        'low_stock' => "📦 $count products are below reorder level",
        'out_of_stock' => "❌ $count products are completely out of stock"
    ];
    
    $message = $messages[$alert_type] ?? "Alert: $alert_type ($count items)";
    
    if ($details) {
        $message .= "\n$details";
    }
    
    return $message;
}

/**
 * Get alert priority level
 */
function get_alert_priority($alert_type) {
    $priorities = [
        'expired' => 'critical',
        'out_of_stock' => 'critical',
        'critical_expiry' => 'high',
        'low_stock' => 'medium',
        'warning_expiry' => 'low'
    ];
    
    return $priorities[$alert_type] ?? 'low';
}

/**
 * Schedule alert notifications (would be called by cron job)
 */
function schedule_alert_notifications($pdo) {
    // Morning summary (8 AM)
    if (date('H') == 8 && date('i') < 5) {
        send_daily_alert_summary($pdo);
    }
    
    // Critical alerts check (every hour during business hours)
    if (date('H') >= 8 && date('H') <= 18) {
        check_and_notify_critical_alerts($pdo);
    }
}
?>