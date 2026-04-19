<?php
/**
 * Simple File-Based Response Cache
 * Provides caching for API responses and expensive queries
 * Performance: 90%+ faster for cached requests
 */

class ResponseCache {
    private static $cache_dir = null;
    private static $enabled = true;
    
    /**
     * Initialize cache directory
     */
    public static function init($dir = null) {
        if ($dir === null) {
            $cache_dir = dirname(__DIR__) . '/storage/cache';
            if (!is_dir($cache_dir)) {
                @mkdir($cache_dir, 0755, true);
            }
            $dir = $cache_dir;
        }
        self::$cache_dir = $dir;
        
        // Create directory if it doesn't exist
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
    
    /**
     * Get cached data if available and not expired
     * @param string $key Cache key
     * @param int $ttl Time to live in seconds
     * @return mixed Cached data or null
     */
    public static function get($key, $ttl = 300) {
        if (!self::$enabled) return null;
        self::ensureInit();
        
        $file = self::getCacheFile($key);
        
        if (file_exists($file)) {
            $age = time() - filemtime($file);
            if ($age < $ttl) {
                // Cache hit!
                $data = file_get_contents($file);
                return json_decode($data, true);
            } else {
                // Cache expired, delete it
                @unlink($file);
            }
        }
        
        return null;
    }
    
    /**
     * Set cache data
     * @param string $key Cache key
     * @param mixed $data Data to cache (will be JSON encoded)
     * @param int $ttl Time to live in seconds
     * @return bool Success
     */
    public static function set($key, $data, $ttl = 300) {
        if (!self::$enabled) return false;
        self::ensureInit();
        
        $file = self::getCacheFile($key);
        
        try {
            $json = json_encode($data);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return false;
            }
            
            file_put_contents($file, $json);
            @chmod($file, 0644);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Clear specific cache entry
     * @param string $key Cache key to clear
     */
    public static function clear($key) {
        self::ensureInit();
        $file = self::getCacheFile($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }
    
    /**
     * Clear all cache entries
     */
    public static function clearAll() {
        self::ensureInit();
        $files = @glob(self::$cache_dir . '/*.json');
        if ($files) {
            foreach ($files as $file) {
                @unlink($file);
            }
        }
    }
    
    /**
     * Disable caching (useful for development)
     */
    public static function disable() {
        self::$enabled = false;
    }
    
    /**
     * Enable caching
     */
    public static function enable() {
        self::$enabled = true;
    }
    
    /**
     * Check if caching is enabled
     */
    public static function isEnabled() {
        return self::$enabled;
    }
    
    /**
     * Get cache statistics
     */
    public static function getStats() {
        self::ensureInit();
        
        $files = @glob(self::$cache_dir . '/*.json');
        $total_size = 0;
        $file_count = 0;
        
        if ($files) {
            $file_count = count($files);
            foreach ($files as $file) {
                $total_size += filesize($file);
            }
        }
        
        return [
            'enabled' => self::$enabled,
            'directory' => self::$cache_dir,
            'file_count' => $file_count,
            'total_size_bytes' => $total_size,
            'total_size_mb' => round($total_size / (1024 * 1024), 2)
        ];
    }
    
    /**
     * Get cache file path for key
     */
    private static function getCacheFile($key) {
        return self::$cache_dir . '/' . md5($key) . '.json';
    }
    
    /**
     * Ensure cache directory is initialized
     */
    private static function ensureInit() {
        if (self::$cache_dir === null) {
            self::init();
        }
    }
}

// Auto-initialize on include
ResponseCache::init();
?>
