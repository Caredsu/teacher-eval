<?php
/**
 * CSRF Middleware
 * Handles CSRF token generation and verification
 */

namespace App\Middleware;

class CsrfMiddleware
{
    /**
     * Generate CSRF token
     */
    public static function generate_token()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Verify CSRF token
     */
    public static function verify_token($token)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Verify token from request
     */
    public static function verify_request($fieldName = 'csrf_token')
    {
        $token = $_POST[$fieldName] ?? $_GET[$fieldName] ?? '';
        return self::verify_token($token);
    }

    /**
     * Protect POST requests
     * Call this to verify CSRF on form submissions
     */
    public static function protect_post()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!self::verify_request()) {
                die('CSRF token verification failed');
            }
        }
    }
}
