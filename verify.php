<?php
// Verification script for TemplateLink Builder
// Run via CLI: php verify.php

require_once __DIR__ . '/app/config/config.php';
require_once __DIR__ . '/app/config/database.php';

echo "=== TemplateLink Builder - Verification Suite ===\n\n";

$errors = 0;

// 1. Validate PHP syntax for all project files
echo "Checking PHP Syntax on MVC files...\n";
$directories = ['app/config', 'app/controllers', 'app/models', 'app/views', 'public'];

foreach ($directories as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (!is_dir($path)) continue;
    
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
    foreach ($files as $file) {
        if ($file->isDir()) continue;
        if ($file->getExtension() !== 'php') continue;
        
        $filename = $file->getRealPath();
        $output = [];
        $result = 0;
        exec("php -l " . escapeshellarg($filename) . " 2>&1", $output, $result);
        
        if ($result !== 0) {
            echo "  [FAIL] Syntax error in file: {$filename}\n";
            echo "         " . implode("\n         ", $output) . "\n";
            $errors++;
        }
    }
}

if ($errors === 0) {
    echo "  [PASS] All PHP files have valid syntax.\n\n";
} else {
    echo "  [FAIL] Syntactical issues found. Please review log errors.\n\n";
}

// 2. Validate Database connection and tables presence
echo "Testing Database connectivity and tables...\n";
try {
    $db = \App\Config\Database::getConnection();
    
    $requiredTables = ['admins', 'categories', 'templates', 'media', 'settings', 'analytics_views', 'analytics_clicks', 'visitor_photos'];
    $missingTables = 0;
    $driver = $db->getAttribute(\PDO::ATTR_DRIVER_NAME);
    
    foreach ($requiredTables as $table) {
        if ($driver === 'sqlite') {
            $stmt = $db->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
            $stmt->execute([$table]);
            $exists = ($stmt->fetch() !== false);
        } else {
            $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
            $exists = ($stmt->rowCount() > 0);
        }
        
        if (!$exists) {
            echo "  [FAIL] Missing Table: {$table}\n";
            $missingTables++;
            $errors++;
        }
    }
    
    if ($missingTables === 0) {
        echo "  [PASS] All required tables exist in database: " . DB_NAME . "\n\n";
    } else {
        echo "  [FAIL] Database schema is incomplete.\n\n";
    }
    
    // Check if seeded
    $adminCheck = $db->query("SELECT COUNT(*) as count FROM admins")->fetch();
    if ((int)$adminCheck['count'] === 0) {
        echo "Database is not seeded. Running seeder...\n";
        require_once __DIR__ . '/database/seed.php';
    } else {
        echo "  [PASS] Database has seeded values.\n\n";
    }
} catch (\Exception $e) {
    echo "  [FAIL] Database connection failed: " . $e->getMessage() . "\n";
    echo "         Please configure your MySQL details in app/config/config.php\n\n";
    $errors++;
}

// Summary
if ($errors === 0) {
    echo "===========================================\n";
    echo " VERIFICATION SUCCESSFUL: App is ready!\n";
    echo "===========================================\n";
} else {
    echo "===========================================\n";
    echo " VERIFICATION FAILED: Please fix errors.\n";
    echo "===========================================\n";
}

exit($errors === 0 ? 0 : 1);
