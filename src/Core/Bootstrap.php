<?php
/**
 * Bootstrap Application
 * Initialize core dependencies and configuration
 */

namespace App\Core;

use MongoDB\Client as MongoClient;

class Bootstrap
{
    private static $instance = null;
    private $container = [];
    
    private function __construct()
    {
        $this->registerBindings();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Register service bindings
     */
    private function registerBindings()
    {
        // Database connection
        $this->container['db'] = function() {
            static $db = null;
            if ($db === null) {
                $db_host = getenv('DB_HOST') ?: '127.0.0.1';
                
                // Check if it's a MongoDB Atlas connection string
                if (strpos($db_host, 'mongodb+srv://') === 0 || strpos($db_host, 'mongodb://') === 0) {
                    // Use as-is if it's already a full connection string
                    $uri = $db_host;
                } else {
                    // Construct URI from host and port
                    $uri = 'mongodb://' . $db_host . ':' . (getenv('DB_PORT') ?: 27017);
                }
                
                $client = new MongoClient($uri);
                $db = $client->selectDatabase(getenv('DB_NAME') ?: 'teacher_evaluation');
            }
            return $db;
        };
        
        // Request instance
        $this->container['request'] = function() {
            return new Request();
        };
    }
    
    /**
     * Resolve from container
     */
    public function make($key)
    {
        if (isset($this->container[$key])) {
            $resolver = $this->container[$key];
            return is_callable($resolver) ? $resolver() : $resolver;
        }
        throw new \Exception("Cannot resolve [$key] from container");
    }
    
    /**
     * Bind to container
     */
    public function bind($key, $value)
    {
        $this->container[$key] = $value;
    }
}
?>
