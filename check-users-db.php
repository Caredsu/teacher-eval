<?php
require_once 'vendor/autoload.php';
require_once 'config/database.php';

global $admins_collection;
$users = $admins_collection->find([], ['sort' => ['created_at' => -1]])->toArray();

echo "Total users: " . count($users) . "\n\n";
foreach ($users as $user) {
    echo "Username: " . ($user['username'] ?? 'N/A') . "\n";
    echo "Email: " . ($user['email'] ?? 'N/A') . "\n";
    echo "Role: " . ($user['role'] ?? 'N/A') . "\n";
    echo "Created By: " . ($user['created_by'] ?? 'MISSING') . "\n";
    echo "Created At: " . ($user['created_at'] ?? 'MISSING') . "\n";
    echo "Status: " . ($user['status'] ?? 'N/A') . "\n";
    echo "Last Login: " . ($user['last_login'] ?? 'MISSING') . "\n";
    echo "---\n";
}
?>
