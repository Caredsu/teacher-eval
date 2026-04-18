<?php
/**
 * Auth Middleware - Verify user is authenticated
 */

namespace App\Middleware;

use App\Core\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle($request, callable $next)
    {
        // Check if user is logged in
        if (!$this->isAuthenticated()) {
            return Response::unauthorized('Authentication required');
        }
        
        return $next($request);
    }
    
    /**
     * Check if user is authenticated
     */
    private function isAuthenticated()
    {
        return isset($_SESSION['admin_id']) && 
               isset($_SESSION['admin_username']) && 
               !empty($_SESSION['admin_id']);
    }
}
?>
