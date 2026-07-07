<?php
namespace App\Controllers;

use App\Models\Admin;

class AuthController extends Controller {
    /**
     * Show the login screen.
     */
    public function showLogin() {
        if ($this->isLoggedIn()) {
            $this->redirect('admin/dashboard');
        }

        $this->render('auth/login', [
            'title' => 'Admin Login'
        ], 'plain');
    }

    /**
     * Process login request.
     */
    public function login() {
        // Validate CSRF
        $this->validateCsrf();

        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $this->setFlash('error', 'Please fill in all fields.');
            $this->redirect('admin/login');
        }

        $adminModel = new Admin();
        $admin = $adminModel->authenticate($username, $password);

        if ($admin) {
            // Prevent Session Hijacking by regenerating session ID
            session_regenerate_id(true);

            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = [
                'id' => $admin['id'],
                'username' => $admin['username'],
                'email' => $admin['email']
            ];

            $this->setFlash('success', 'Welcome back, ' . htmlspecialchars($admin['username']) . '!');
            $this->redirect('admin/dashboard');
        } else {
            $this->setFlash('error', 'Invalid username or password.');
            $this->redirect('admin/login');
        }
    }

    /**
     * Process logout request.
     */
    public function logout() {
        // Clear all session variables
        $_SESSION = [];

        // Destroy cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        session_destroy();

        // Start new empty session to have CSRF protection work on login page
        session_start();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        $this->redirect('admin/login');
    }
}
