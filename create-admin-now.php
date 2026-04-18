<?php
// Direct admin creation script
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

echo "=== Creating Admin User ===\n\n";

// Get global collections
global $admins_collection;

// Admin credentials
$username = 'admin';
$email = 'admin@fullbright.edu.ph';
$password = 'admin123';
$role = 'admin';

try {
    // Check if admin already exists
    $existing = $admins_collection->findOne(['username' => $username]);
    
    if ($existing) {
        echo "⚠️  Admin user '$username' already exists!\n";
        echo "   ID: " . $existing['_id'] . "\n";
        echo "   Email: " . $existing['email'] . "\n";
    } else {
        // Create new admin
        echo "Creating new admin user...\n";
        $hashed_password = hashPassword($password);
        
        $result = $admins_collection->insertOne([
            'username' => $username,
            'email' => $email,
            'password' => $hashed_password,
            'role' => $role,
            'status' => 'active',
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'updated_at' => new MongoDB\BSON\UTCDateTime(),
            'created_by' => 'system',
            'last_login' => null
        ]);
        
        if ($result->getInsertedId()) {
            echo "✅ SUCCESS! Admin user created!\n\n";
            echo "Username: $username\n";
            echo "Email: $email\n";
            echo "Password: $password\n";
            echo "Role: $role\n";
            echo "\n📝 You can now login at: http://localhost/teacher-eval/admin/login.php\n";
        }
    }
    
    // List all admins
    echo "\n=== All Admin Users ===\n";
    $admins = $admins_collection->find();
    foreach ($admins as $admin) {
        echo "- " . $admin['username'] . " (" . $admin['email'] . ")\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Code: " . $e->getCode() . "\n";
}
?>
