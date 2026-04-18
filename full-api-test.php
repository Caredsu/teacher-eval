<?php
/**
 * Complete API Test - Simulates real request
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== ADMIN USERS API TEST ===\n\n";

// Step 1: Setup session
echo "1. Starting session...\n";
session_start();
$_SESSION['admin_id'] = 'test_admin_id';
$_SESSION['admin_role'] = 'admin';
echo "   ✓ Session started\n";
echo "   admin_id: " . $_SESSION['admin_id'] . "\n";
echo "   admin_role: " . $_SESSION['admin_role'] . "\n\n";

// Step 2: Load config
echo "2. Loading database config...\n";
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';
echo "   ✓ Config loaded\n\n";

// Step 3: Check collections
echo "3. Checking MongoDB collections...\n";
global $admins_collection, $db;

if (!isset($admins_collection)) {
    echo "   ❌ ERROR: admins_collection not set\n";
    exit;
}
echo "   ✓ admins_collection available\n\n";

// Step 4: Test database query
echo "4. Testing database query...\n";
try {
    $count = $admins_collection->countDocuments([]);
    echo "   ✓ Found $count users in database\n";
    
    $users = $admins_collection->find([], ['sort' => ['created_at' => -1]])->toArray();
    echo "   ✓ Retrieved " . count($users) . " users\n\n";
    
    // Step 5: Format response
    echo "5. Formatting response...\n";
    $formatted_users = [];
    foreach ($users as $user) {
        $created_at = $user['created_at'] ?? null;
        $last_login = $user['last_login'] ?? null;
        
        // Handle both DateTime objects and strings
        if (is_object($created_at) && method_exists($created_at, 'toDateTime')) {
            $created_at = $created_at->toDateTime()->format('Y-m-d H:i:s');
        } elseif (is_string($created_at)) {
            $created_at = $created_at;
        } else {
            $created_at = '';
        }
        
        if (is_object($last_login) && method_exists($last_login, 'toDateTime')) {
            $last_login = $last_login->toDateTime()->format('Y-m-d H:i:s');
        } elseif (is_string($last_login)) {
            $last_login = $last_login;
        } else {
            $last_login = 'Never';
        }
        
        $formatted_users[] = [
            '_id' => (string)$user['_id'],
            'username' => $user['username'] ?? '',
            'email' => $user['email'] ?? '',
            'role' => $user['role'] ?? 'admin',
            'status' => $user['status'] ?? 'active',
            'created_at' => $created_at,
            'created_by' => $user['created_by'] ?? 'system',
            'last_login' => $last_login
        ];
    }
    echo "   ✓ " . count($formatted_users) . " users formatted\n\n";
    
    // Step 6: Output JSON
    echo "6. Output JSON response:\n";
    echo "=====================\n";
    
    $response = [
        'success' => true,
        'message' => 'Success',
        'data' => $formatted_users
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    echo "\n\n=====================\n";
    echo "✓ ALL TESTS PASSED!\n";
    
} catch (Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . "\n";
    echo "   Line: " . $e->getLine() . "\n";
    exit;
}
?>
