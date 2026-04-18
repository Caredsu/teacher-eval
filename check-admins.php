<?php
// Check if admin users exist in database
require_once __DIR__ . '/config/database.php';

echo "=== Checking for Admin Users ===\n\n";

try {
    $db = getMongoConnection()->teacher_eval;
    
    // Check admins collection
    $count = $db->admins->countDocuments([]);
    echo "Total admin users in database: $count\n\n";
    
    if ($count > 0) {
        echo "Admin users found:\n";
        $admins = $db->admins->find([], ['projection' => ['username' => 1, 'email' => 1, 'role' => 1]]);
        foreach ($admins as $admin) {
            echo "  - " . ($admin['username'] ?? 'N/A') . " (" . ($admin['email'] ?? 'N/A') . ")\n";
        }
    } else {
        echo "❌ NO ADMIN USERS FOUND!\n";
        echo "You need to create an admin user first!\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
