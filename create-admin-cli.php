<?php
/**
 * Create Admin Account - Command Line
 * Usage: php create-admin-cli.php <username> <email> <password> [role]
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

if ($argc < 4) {
    echo "Usage: php create-admin-cli.php <username> <email> <password> [role]\n";
    echo "Example: php create-admin-cli.php admin admin@example.com password123 admin\n";
    exit(1);
}

$username = $argv[1];
$email = $argv[2];
$password = $argv[3];
$role = $argv[4] ?? 'admin';

echo "Creating admin account...\n";
echo "Username: $username\n";
echo "Email: $email\n";
echo "Role: $role\n";

try {
    global $admins_collection;
    
    if (!isset($admins_collection)) {
        echo "ERROR: Database not connected!\n";
        exit(1);
    }
    
    // Check if exists
    $existing = $admins_collection->findOne(['username' => $username]);
    if ($existing) {
        echo "ERROR: Username already exists!\n";
        exit(1);
    }
    
    $hashed_password = hashPassword($password);
    
    $result = $admins_collection->insertOne([
        'username' => $username,
        'email' => $email,
        'password' => $hashed_password,
        'role' => $role,
        'status' => 'active',
        'created_at' => new MongoDB\BSON\UTCDateTime(),
        'updated_at' => new MongoDB\BSON\UTCDateTime(),
        'last_login' => null
    ]);
    
    if ($result->getInsertedId()) {
        echo "\n✅ SUCCESS! Admin account created!\n";
        echo "ID: " . $result->getInsertedId() . "\n";
        echo "\nYou can now login at:\n";
        echo "http://localhost/teacher-eval/admin/login.php\n";
        echo "\nUsername: $username\n";
        echo "Password: $password\n";
    } else {
        echo "ERROR: Failed to insert!\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}
?>
