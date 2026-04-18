<?php
/**
 * Role Middleware - Check user has required role
 */

namespace App\Middleware;

use App\Core\Response;

class RoleMiddleware implements MiddlewareInterface
{
    private $required_roles;
    
    public function __construct($roles = [])
    {
        // Can be string or array
        $this->required_roles = is_array($roles) ? $roles : [$roles];
    }
    
    public function handle($request, callable $next)
    {
        // First check authentication
        if (!$this->isAuthenticated()) {
            return Response::unauthorized('Authentication required');
        }
        
        // Then check role
        if (!$this->hasRole()) {
            return Response::forbidden('Insufficient permissions for this action');
        }
        
        return $next($request);
    }
    
    /**
     * Check if user is authenticated
     */
    private function isAuthenticated()
    {
        return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
    }
    
    /**
     * Check if user has required role
     */
    private function hasRole()
    {
        if (empty($this->required_roles)) {
            return true; // No role restriction
        }
        
        $userRole = $_SESSION['admin_role'] ?? 'guest';
        return in_array($userRole, $this->required_roles);
    }
}
?>
