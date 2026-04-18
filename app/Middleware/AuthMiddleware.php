<?php
/**
 * Authentication Middleware
 * Checks if user is logged in and redirects to login if not
 */

namespace App\Middleware;

class AuthMiddleware
{
    /**
     * Get base URL for redirects (handles both local /teacher-eval and production /)
     */
    private static function getBaseUrl()
    {
        // Check if it's production (Render.com or any non-localhost)
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isProduction = strpos($host, 'localhost') === false && strpos($host, '127.0.0.1') === false;
        
        return $isProduction ? '' : '/teacher-eval';
    }

    /**
     * Require authentication
     * Call this at the beginning of protected pages
     */
    public static function require_login()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['admin_id']) || empty($_SESSION['admin_id'])) {
            $baseUrl = self::getBaseUrl();
            header('Location: ' . $baseUrl . '/admin/login.php');
            exit;
        }
    }

    /**
     * Require admin role
     */
    public static function require_admin()
    {
        self::require_login();

        if (($_SESSION['admin_role'] ?? '') !== 'admin') {
            $baseUrl = self::getBaseUrl();
            header('Location: ' . $baseUrl . '/admin/dashboard.php');
            exit;
        }
    }

    /**
     * Require guest (not logged in)
     * Used on login page
     */
    public static function require_guest()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id'])) {
            $baseUrl = self::getBaseUrl();
            header('Location: ' . $baseUrl . '/admin/dashboard.php');
            exit;
        }
    }

    /**
     * Get current admin session
     */
    public static function current_admin()
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
}
