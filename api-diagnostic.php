<?php
/**
 * Quick diagnostic endpoint to check if API is working
 * Access: /teacher-eval/api-diagnostic.php
 */
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'tests' => [
        'php_loaded' => 'OK',
        'message' => 'If you see this, PHP is working'
    ]
], JSON_PRETTY_PRINT);

// Try to load helpers
try {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/app/Constants.php';
    require_once __DIR__ . '/includes/helpers.php';
    require_once __DIR__ . '/config/database.php';
    
    echo "\n✅ All files loaded successfully\n";
    
    // Try a simple query
    if (isset($teachers_collection)) {
        $count = $teachers_collection->countDocuments();
        echo "✅ Teachers collection: $count documents\n";
    } else {
        echo "❌ Teachers collection not initialized\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
