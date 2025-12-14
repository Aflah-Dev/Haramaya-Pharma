<?php
//HARAMAYA PHARMA - Logout Handler


$pdo = require __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/auth.php';

secure_session_start();
logout_user($pdo);
header('Location: login.php');
exit;
