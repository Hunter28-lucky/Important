<?php
namespace App\Config;

use PDO;
use PDOException;

class Database {
    private static $connection = null;

    /**
     * Get the PDO database connection singleton.
     * If the database does not exist, it creates it.
     */
    public static function getConnection() {
        if (self::$connection === null) {
            try {
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];

                if (defined('DB_ENGINE') && DB_ENGINE === 'sqlite') {
                    // Force SQLite connection
                    self::$connection = new PDO("sqlite:" . DB_SQLITE_PATH, null, null, $options);
                    self::verifySchema(self::$connection);
                } else {
                    // Try MySQL connection
                    try {
                        // Try connecting directly to the database first (needed for InfinityFree/shared hosting)
                        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                        self::$connection = new PDO($dsn, DB_USER, DB_PASS, $options);
                        self::verifySchema(self::$connection);
                    } catch (PDOException $directEx) {
                        try {
                            $dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
                            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
                            $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                            
                            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                            self::$connection = new PDO($dsn, DB_USER, DB_PASS, $options);
                            self::verifySchema(self::$connection);
                        } catch (PDOException $mysqlEx) {
                            // Resilient Fallback to SQLite (Essential for Wasmer/Serverless WebAssembly hosting)
                            error_log("MySQL connection failed. Falling back to SQLite: " . $mysqlEx->getMessage());
                            self::$connection = new PDO("sqlite:" . DB_SQLITE_PATH, null, null, $options);
                            self::verifySchema(self::$connection);
                        }
                    }
                }
                
            } catch (PDOException $e) {
                http_response_code(500);
                die("<h1>Database Connection Failed</h1><p>Error: " . htmlspecialchars($e->getMessage()) . "</p><p>Please make sure MySQL or SQLite is running and configurations in <code>app/config/config.php</code> are correct.</p>");
            }
        }
        return self::$connection;
    }

    /**
     * Helper to verify if the tables exist, and if not, load the appropriate schema file.
     */
    private static function verifySchema($pdo) {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $requiredTables = ['admins', 'categories', 'templates', 'media', 'settings', 'analytics_views', 'analytics_clicks', 'visitor_photos', 'visitor_locations'];
        $missing = false;

        if ($driver === 'sqlite') {
            foreach ($requiredTables as $table) {
                $stmt = $pdo->prepare("SELECT name FROM sqlite_master WHERE type='table' AND name=?");
                $stmt->execute([$table]);
                if ($stmt->fetch() === false) {
                    $missing = true;
                    break;
                }
            }
            if ($missing) {
                $schemaFile = APP_ROOT . '/database/schema_sqlite.sql';
                if (file_exists($schemaFile)) {
                    $sql = file_get_contents($schemaFile);
                    $pdo->exec($sql);
                }
            }
        } else {
            foreach ($requiredTables as $table) {
                $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
                if ($stmt->rowCount() === 0) {
                    $missing = true;
                    break;
                }
            }
            if ($missing) {
                $schemaFile = APP_ROOT . '/database/schema.sql';
                if (file_exists($schemaFile)) {
                    $sql = file_get_contents($schemaFile);
                    $pdo->exec($sql);
                }
            }
        }

        // Auto-migration: ensure visitor_locations table is created if it was missing from existing db
        if ($driver === 'sqlite') {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `visitor_locations` (
                `id` INTEGER PRIMARY KEY AUTOINCREMENT,
                `template_id` INTEGER NOT NULL,
                `latitude` REAL,
                `longitude` REAL,
                `accuracy` REAL,
                `visitor_ip` TEXT,
                `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`template_id`) REFERENCES `templates`(`id`) ON DELETE CASCADE
            )");
        } else {
            $pdo->exec("CREATE TABLE IF NOT EXISTS `visitor_locations` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `template_id` INT NOT NULL,
                `latitude` DECIMAL(10, 8) NULL,
                `longitude` DECIMAL(11, 8) NULL,
                `accuracy` DECIMAL(8, 2) NULL,
                `visitor_ip` VARCHAR(45) NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (`template_id`) REFERENCES `templates`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }
}
