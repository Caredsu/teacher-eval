<?php
/**
 * Middleware Pipeline - Chain multiple middleware
 */

namespace App\Middleware;

class MiddlewarePipeline
{
    private $middleware = [];
    private $handler;
    
    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }
    
    /**
     * Add middleware to pipeline
     */
    public function through($middleware)
    {
        $this->middleware[] = $middleware;
        return $this;
    }
    
    /**
     * Execute pipeline
     */
    public function execute($request)
    {
        $pipeline = array_reduce(
            array_reverse($this->middleware),
            $this->carry(),
            $this->handler
        );
        
        return $pipeline($request);
    }
    
    /**
     * Carry function for reducing middleware
     */
    private function carry()
    {
        return function($next, $middleware) {
            return function($request) use ($middleware, $next) {
                if (is_string($middleware)) {
                    // Instantiate middleware class
                    $middleware = new $middleware();
                }
                
                return $middleware->handle($request, $next);
            };
        };
    }
}
?>
