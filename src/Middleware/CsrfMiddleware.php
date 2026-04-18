<?php
/**
 * CSRF Middleware - Validate CSRF tokens
 */

namespace App\Middleware;

use App\Core\Response;

class CsrfMiddleware implements MiddlewareInterface
{
    public function handle($request, callable $next)
    {
        // Skip CSRF check for GET requests
        if (strtoupper($_SERVER['REQUEST_METHOD']) === 'GET') {
            return $next($request);
        }
        
        // Skip CSRF check for login endpoint
        if (strpos($_SERVER['REQUEST_URI'], 'auth/login') !== false) {
            return $next($request);
        }
        
        // Verify CSRF token
        if (!$this->validateToken()) {
            return Response::error('CSRF token validation failed', 403);
        }
        
        return $next($request);
    }
    
    /**
     * Validate CSRF token
     */
    private function validateToken()
    {
        // Check if token in session
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        // Get token from request (header or body)
        $token = $this->getTokenFromRequest();
        if (empty($token)) {
            return false;
        }
        
        // Verify token matches
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Get CSRF token from request
     */
    private function getTokenFromRequest()
    {
        // Check X-CSRF-Token header
        if (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            return $_SERVER['HTTP_X_CSRF_TOKEN'];
        }
        
        // Check POST data
        if (isset($_POST['csrf_token'])) {
            return $_POST['csrf_token'];
        }
        
        // Check JSON body
        $json = json_decode(file_get_contents('php://input'), true);
        if (isset($json['csrf_token'])) {
            return $json['csrf_token'];
        }
        
        return null;
    }
}
?>
