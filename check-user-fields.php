<?php
// Check database fields
require_once __DIR__ . '/config/database.php';

global $admins_collection;
$user = $admins_collection->findOne([]);

if ($user) {
    echo "Current user fields in database:\n";
    echo json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
} else {
    echo "No users found";
}
?>
