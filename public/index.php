<?php
// Route static assets directly if running inside PHP's built-in webserver (WASM/Wasmer Edge)
if (php_sapi_name() === 'cli-server') {
    $filePath = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (is_file($filePath)) {
        return false;
    }
}

// Include config & session setup
require_once dirname(__DIR__) . '/app/config/config.php';

// PSR-4 Autoloader mapping "App\" namespace to "/app/" directory
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = dirname(__DIR__) . '/app/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // Move to next registered autoloader
    }
    
    $relative_class = substr($class, $len);
    $parts = explode('\\', $relative_class);
    
    // Convert directory parts to lowercase (e.g. Config -> config, Controllers -> controllers)
    if (count($parts) > 1) {
        for ($i = 0; $i < count($parts) - 1; $i++) {
            $parts[$i] = strtolower($parts[$i]);
        }
    }
    
    $className = array_pop($parts);
    $subDir = count($parts) > 0 ? implode('/', $parts) . '/' : '';
    
    // Try original case file (e.g. AdminController.php)
    $fileOriginal = $base_dir . $subDir . $className . '.php';
    if (file_exists($fileOriginal)) {
        require_once $fileOriginal;
        return;
    }
    
    // Try lowercase filename fallback (e.g. database.php)
    $fileLower = $base_dir . $subDir . strtolower($className) . '.php';
    if (file_exists($fileLower)) {
        require_once $fileLower;
        return;
    }
});

// Resolve Database connection early to verify connectivity/schema
App\Config\Database::getConnection();

// Parse Request URI and Method
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

// Handle subdirectory deploys
$basePath = str_replace('/public/index.php', '', $scriptName);
if ($basePath && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Extract path without query parameters
$path = parse_url($requestUri, PHP_URL_PATH);
$path = trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Load route mappings
$routes = require_once APP_ROOT . '/app/config/routes.php';

$controllerAction = null;
$routeParams = [];

// 1. Match static routes
if (isset($routes[$method][$path])) {
    $controllerAction = $routes[$method][$path];
} elseif ($method === 'GET' && empty($path)) {
    // Root path direct match
    $controllerAction = $routes['GET'][''] ?? null;
}

// 2. Match dynamic route: view/{slug}
if (!$controllerAction && $method === 'GET' && strpos($path, 'view/') === 0) {
    $slug = substr($path, 5); // strip out 'view/'
    if (!empty($slug)) {
        $controllerAction = 'ViewerController@view';
        $routeParams = [$slug];
    }
}

// Dispatch the request
if ($controllerAction) {
    list($controllerName, $action) = explode('@', $controllerAction);
    $fullControllerClass = "App\\Controllers\\" . $controllerName;
    
    if (class_exists($fullControllerClass)) {
        $controller = new $fullControllerClass();
        if (method_exists($controller, $action)) {
            // Invoke the controller action method
            call_user_func_array([$controller, $action], $routeParams);
            exit;
        }
    }
}

// Default 404 Fallback
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Page Not Found</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            color: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        .container {
            max-width: 500px;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.03);
            border: 1px rgba(255, 255, 255, 0.08) solid;
            backdrop-filter: blur(20px);
            border-radius: 24px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
        }
        h1 { font-size: 6rem; margin: 0 0 1rem 0; background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        h2 { font-size: 1.5rem; margin-bottom: 1rem; color: #e2e8f0; }
        p { color: #94a3b8; line-height: 1.6; margin-bottom: 2rem; }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: white;
            padding: 0.8rem 2rem;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(79, 70, 229, 0.6);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>404</h1>
        <h2>Page Not Found</h2>
        <p>The template link or admin page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
        <a href="<?= BASE_URL ?>admin" class="btn">Go to Dashboard</a>
    </div>
</body>
</html>
