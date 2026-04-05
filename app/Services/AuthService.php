<?php
/**
 * Authentication Service
 * Handles user login/logout and password management
 */

namespace App\Services;

class AuthService
{
    private $adminModel;

    public function __construct($adminModel)
    {
        $this->adminModel = $adminModel;
    }

    /**
     * Authenticate user
     */
    public function authenticate($username, $password)
    {
        $admin = $this->adminModel->getByUsername($username);

        if (!$admin) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        if (!password_verify($password, $admin['password'])) {
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        return [
            'success' => true,
            'admin' => $admin
        ];
    }

    /**
     * Login admin
     */
    public function login($admin)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['admin_id'] = (string)$admin['_id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
        $_SESSION['admin_email'] = $admin['email'];

        return true;
    }

    /**
     * Logout admin
     */
    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        session_destroy();
        return true;
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
    }

    /**
     * Get current admin
     */
    public function getCurrentAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return [
            'id' => $_SESSION['admin_id'] ?? null,
            'username' => $_SESSION['admin_username'] ?? null,
            'role' => $_SESSION['admin_role'] ?? 'admin',
            'email' => $_SESSION['admin_email'] ?? null
        ];
    }

    /**
     * Check if user is super admin
     */
    public function isSuperAdmin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return ($_SESSION['admin_role'] ?? '') === 'superadmin';
    }

    /**
     * Hash password
     */
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify password
     */
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * Change password
     */
    public function changePassword($adminId, $currentPassword, $newPassword)
    {
        $admin = $this->adminModel->getById($adminId);

        if (!$admin) {
            return ['success' => false, 'message' => 'Admin not found'];
        }

        if (!$this->verifyPassword($currentPassword, $admin['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }

        $hashedPassword = $this->hashPassword($newPassword);
        $this->adminModel->updatePassword($adminId, $hashedPassword);

        return ['success' => true, 'message' => 'Password updated successfully'];
    }
}
