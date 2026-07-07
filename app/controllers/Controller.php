<?php
namespace App\Controllers;

class Controller {
    /**
     * Render a view file with optionally extracted data parameters.
     */
    protected function render($viewPath, $data = [], $layout = 'admin') {
        // Extract variables to local scope
        extract($data);

        // Capture view output
        ob_start();
        $viewFile = APP_ROOT . '/app/views/' . $viewPath . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            die("View file not found: " . htmlspecialchars($viewPath));
        }
        $content = ob_get_clean();

        // Render full layout or just content
        if ($layout === 'admin') {
            require APP_ROOT . '/app/views/layout/admin_head.php';
            echo $content;
            require APP_ROOT . '/app/views/layout/admin_foot.php';
        } elseif ($layout === 'plain') {
            echo $content;
        } else {
            // Render specific layouts if needed
            echo $content;
        }
    }

    /**
     * Return JSON response and terminate.
     */
    protected function json($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    /**
     * Redirect to path.
     */
    protected function redirect($path) {
        header('Location: ' . BASE_URL . ltrim($path, '/'));
        exit;
    }

    /**
     * Check if administrator is logged in.
     */
    protected function isLoggedIn() {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }

    /**
     * Enforce authentication. Redirects to login if not authenticated.
     */
    protected function requireAuth() {
        if (!$this->isLoggedIn()) {
            $this->setFlash('error', 'You must log in to access this page.');
            $this->redirect('admin/login');
        }
    }

    /**
     * Set a flash message in the session.
     */
    protected function setFlash($key, $message) {
        $_SESSION['flash'][$key] = $message;
    }

    /**
     * Check if a flash message exists.
     */
    protected function hasFlash($key) {
        return isset($_SESSION['flash'][$key]);
    }

    /**
     * Get a flash message and delete it from session.
     */
    protected function getFlash($key) {
        if ($this->hasFlash($key)) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $message;
        }
        return null;
    }

    /**
     * Validate the CSRF token from request headers or POST body.
     */
    protected function validateCsrf() {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        
        if (empty($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            $this->json(['error' => 'Invalid CSRF token. Request blocked.'], 403);
        }
    }

    /**
     * Sanitize input helper for basic XSS protection.
     */
    protected function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
}
