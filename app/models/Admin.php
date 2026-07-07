<?php
namespace App\Models;

class Admin extends Model {
    /**
     * Authenticate an admin using their username or email and password.
     * Returns the admin record on success, or false on failure.
     */
    public function authenticate($username, $password) {
        $admin = $this->fetch(
            "SELECT * FROM admins WHERE username = :username OR email = :username LIMIT 1", 
            ['username' => $username]
        );

        if ($admin && password_verify($password, $admin['password_hash'])) {
            return $admin;
        }
        return false;
    }

    /**
     * Create a new administrator account.
     */
    public function create($username, $email, $password) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        return $this->query(
            "INSERT INTO admins (username, email, password_hash) VALUES (:username, :email, :password_hash)",
            [
                'username' => $username,
                'email' => $email,
                'password_hash' => $hash
            ]
        );
    }
}
