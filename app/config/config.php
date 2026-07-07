<?php
// Configuration settings for TemplateLink Builder

// Error Reporting (Development Mode - set to 0 in Production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Base URLs and Paths
define('APP_NAME', 'TemplateLink Builder');
define('APP_ROOT', dirname(dirname(__DIR__)));

// Determine Base URL dynamically (reverse-proxy aware for Wasmer Edge/Cloudflared)
$protocol = 'http://';
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $protocol = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) . '://';
} elseif (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    $protocol = 'https://';
}

$host = 'localhost:8000';
if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
} elseif (isset($_SERVER['HTTP_HOST'])) {
    $host = $_SERVER['HTTP_HOST'];
}

// Find subdirectories if not running at root level
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$subDir = str_replace('/public/index.php', '', $scriptName);
$subDir = trim($subDir, '/');
$baseUrl = $protocol . $host . ($subDir ? '/' . $subDir : '') . '/';
define('BASE_URL', $baseUrl);

// Directory constants
define('UPLOAD_DIR', APP_ROOT . '/public/uploads');
define('UPLOAD_URL', BASE_URL . 'uploads/');

// Database Configurations
define('DB_ENGINE', 'mysql'); // Toggle: 'mysql' or 'sqlite'
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'templatelink_builder');
define('DB_SQLITE_PATH', APP_ROOT . '/database/database.sqlite');


// Start session if not started, with secure properties
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'cookie_samesite' => 'Lax'
    ]);
}

// Generate CSRF Token if not set
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
