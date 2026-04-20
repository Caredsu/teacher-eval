<?php
/**
 * Debug script to test API responses
 * Access: /teacher-eval/api-test-debug.php
 */

header('Content-Type: application/json; charset=utf-8');

// Test 1: Check if database.php loads without errors
echo "{\n  \"test_results\": [\n";

$results = [];

// Test database connection
try {
    require_once __DIR__ . '/config/database.php';
    $results[] = [
        'test' => 'Database Connection',
        'status' => 'OK',
        'message' => 'database.php loaded successfully'
    ];
} catch (Exception $e) {
    $results[] = [
        'test' => 'Database Connection',
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ];
}

// Test helpers
try {
    require_once __DIR__ . '/includes/helpers.php';
    $results[] = [
        'test' => 'Helpers',
        'status' => 'OK',
        'message' => 'helpers.php loaded successfully'
    ];
} catch (Exception $e) {
    $results[] = [
        'test' => 'Helpers',
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ];
}

// Test API teachers endpoint
global $ORIGINAL_REQUEST_PATH;
$ORIGINAL_REQUEST_PATH = 'api/teachers/test123';

try {
    // Check if getIdFromPath works
    $id = getIdFromPath();
    $results[] = [
        'test' => 'getIdFromPath Function',
        'status' => 'OK',
        'path' => $ORIGINAL_REQUEST_PATH,
        'extracted_id' => $id,
        'expected' => 'test123'
    ];
} catch (Exception $e) {
    $results[] = [
        'test' => 'getIdFromPath Function',
        'status' => 'ERROR',
        'message' => $e->getMessage()
    ];
}

// Output results
echo implode(",\n", array_map(function($r) { 
    return json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); 
}, $results));

echo "\n  ],\n";
echo "  \"timestamp\": \"" . date('Y-m-d H:i:s') . "\"\n";
echo "}\n";
?>
