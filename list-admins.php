<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

echo "=== ALL ADMIN USERS IN DATABASE ===\n\n";

try {
    global $admins_collection;
    
    $admins = $admins_collection->find([])->toArray();
    $count = count($admins);
    
    echo "Total admins: $count\n\n";
    
    foreach ($admins as $idx => $admin) {
        echo "Admin #" . ($idx + 1) . ":\n";
        echo "  Username: " . ($admin['username'] ?? 'N/A') . "\n";
        echo "  Email: " . ($admin['email'] ?? 'N/A') . "\n";
        echo "  Role: " . ($admin['role'] ?? 'N/A') . "\n";
        echo "  Status: " . ($admin['status'] ?? 'active') . "\n";
        echo "  Password: " . substr($admin['password'] ?? $admin['password_hashed'] ?? '', 0, 40) . "...\n";
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>
