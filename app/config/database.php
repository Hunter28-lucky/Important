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
        $requiredTables = ['admins', 'categories', 'templates', 'media', 'settings', 'analytics_views', 'analytics_clicks', 'visitor_photos'];
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
    }
}
