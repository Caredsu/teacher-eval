<?php
/**
 * Middleware Interface
 * All middleware must implement this interface
 */

namespace App\Middleware;

interface MiddlewareInterface
{
    /**
     * Handle the middleware
     * 
     * @param mixed $request The request object
     * @param callable $next The next middleware/handler
     * @return mixed The response
     */
    public function handle($request, callable $next);
}
?>
