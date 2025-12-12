<?php
/**
 * HARAMAYA PHARMA - Main Entry Point
 * Admin/Staff Pharmacy Management System
 * Redirects to appropriate page based on authentication status
 */

require_once __DIR__ . '/includes/security.php';
require_once __DIR__ . '/includes/auth.php';

secure_session_start();

// Redirect to dashboard if logged in, otherwise to login
if (is_logged_in()) {
    header('Location: modules/dashboard/index.php');
} else {
    header('Location: modules/auth/login.php');
}
exit;
