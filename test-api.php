<?php
/**
 * Test API endpoint to diagnose issues
 */
header('Content-Type: application/json; charset=utf-8');

$results = [];

// Test 1: Can we load config files?
try {
    require_once __DIR__ . '/app/Constants.php';
    $results[] = ['test' => 'Constants loaded', 'status' => 'OK'];
} catch (Exception $e) {
    $results[] = ['test' => 'Constants loaded', 'status' => 'FAILED', 'error' => $e->getMessage()];
}

// Test 2: Can we load helpers?
try {
    require_once __DIR__ . '/includes/helpers.php';
    $results[] = ['test' => 'Helpers loaded', 'status' => 'OK'];
} catch (Exception $e) {
    $results[] = ['test' => 'Helpers loaded', 'status' => 'FAILED', 'error' => $e->getMessage()];
}

// Test 3: Check database configuration
try {
    require_once __DIR__ . '/vendor/autoload.php';
    $results[] = [
        'test' => 'Composer autoload',
        'status' => 'OK',
        'db_host' => defined('DB_HOST') ? (DB_HOST ? '***' : 'NULL') : 'NOT DEFINED',
        'db_name' => defined('DB_NAME') ? DB_NAME : 'NOT DEFINED'
    ];
} catch (Exception $e) {
    $results[] = ['test' => 'Composer autoload', 'status' => 'FAILED', 'error' => $e->getMessage()];
}

// Test 4: Try to connect to database
try {
    require_once __DIR__ . '/config/database.php';
    $results[] = ['test' => 'Database connection', 'status' => 'OK'];
} catch (Exception $e) {
    $results[] = ['test' => 'Database connection', 'status' => 'FAILED', 'error' => $e->getMessage()];
}

// Test 5: Check if teachers collection exists and has data
if (isset($teachers_collection)) {
    try {
        $count = $teachers_collection->countDocuments();
        $results[] = ['test' => 'Teachers collection query', 'status' => 'OK', 'teacher_count' => $count];
    } catch (Exception $e) {
        $results[] = ['test' => 'Teachers collection query', 'status' => 'FAILED', 'error' => $e->getMessage()];
    }
} else {
    $results[] = ['test' => 'Teachers collection', 'status' => 'FAILED', 'error' => 'Collection not initialized'];
}

echo json_encode([
    'timestamp' => date('Y-m-d H:i:s'),
    'test_results' => $results,
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
