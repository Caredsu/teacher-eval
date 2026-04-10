<?php
/**
 * Check Admin User Record in Database
 */

require_once 'vendor/autoload.php';
require_once 'config/database.php';

global $admins_collection;

echo "=== Admin User Record Check ===\n\n";

// Get admin user
$admin = $admins_collection->findOne(['username' => 'admin']);

if ($admin) {
    echo "Admin record found!\n\n";
    echo "Fields in database:\n";
    foreach ($admin as $key => $value) {
        if ($key === '_id') {
            echo "  $key: " . $value . "\n";
        } elseif ($key === 'created_at' || $key === 'updated_at' || $key === 'last_login') {
            echo "  $key: " . $value . "\n";
        } else {
            echo "  $key: " . $value . "\n";
        }
    }
    
    echo "\n\nMissing fields:\n";
    $required_fields = ['_id', 'username', 'email', 'password', 'role', 'status', 'created_at', 'created_by', 'updated_at', 'updated_by', 'last_login'];
    
    foreach ($required_fields as $field) {
        if (!isset($admin[$field])) {
            echo "  ❌ $field: MISSING\n";
        } else {
            echo "  ✓ $field: OK\n";
        }
    }
} else {
    echo "Admin user NOT found!\n";
}

echo "\n\n=== All three users comparison ===\n\n";

$all_users = $admins_collection->find([], ['sort' => ['username' => 1]])->toArray();

foreach ($all_users as $user) {
    echo "Username: " . $user['username'] . "\n";
    echo "  - created_by: " . ($user['created_by'] ?? 'MISSING') . "\n";
    echo "  - last_login: " . ($user['last_login'] ?? 'NULL') . "\n";
    echo "  - password: " . (isset($user['password']) ? 'YES' : 'MISSING') . "\n";
    echo "  - role: " . ($user['role'] ?? 'MISSING') . "\n";
    echo "\n";
}
?>
