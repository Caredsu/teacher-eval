<?php
require_once __DIR__ . '/config/database.php';

global $admins_collection;
$users = $admins_collection->find([])->toArray();

echo "All users and their fields:\n\n";
foreach ($users as $user) {
    echo "User: " . $user['username'] . "\n";
    echo "  created_by: " . ($user['created_by'] ?? 'NOT SET') . "\n";
    echo "  updated_by: " . ($user['updated_by'] ?? 'NOT SET') . "\n";
    echo "  created_at: " . ($user['created_at'] ?? 'NOT SET') . "\n";
    echo "  updated_at: " . ($user['updated_at'] ?? 'NOT SET') . "\n";
    echo "\n";
}
?>
