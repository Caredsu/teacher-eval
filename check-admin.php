<?php
/**
 * Simple Admin Check
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Check</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 5px; }
        h2 { color: #333; border-bottom: 2px solid #0066cc; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #0066cc; color: white; }
        .good { color: green; font-weight: bold; }
        .bad { color: red; font-weight: bold; }
        code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>

<h1>🔍 Check Admin Users in Database</h1>

<?php

try {
    global $admins_collection;
    
    echo '<div class="box">';
    echo '<h2>Database: ' . DB_NAME . '</h2>';
    
    // Count admins
    $count = $admins_collection->countDocuments([]);
    
    if ($count == 0) {
        echo '<p class="bad">❌ No admin users found!</p>';
        echo '<p>You need to create an admin user first.</p>';
    } else {
        echo '<p class="good">✅ Found ' . $count . ' admin user(s)</p>';
        
        // Get all admins
        $admins = $admins_collection->find([])->toArray();
        
        echo '<table>';
        echo '<tr>';
        echo '<th>Username</th>';
        echo '<th>Email</th>';
        echo '<th>Role</th>';
        echo '<th>Status</th>';
        echo '<th>Password Field</th>';
        echo '<th>Hash (first 50 chars)</th>';
        echo '</tr>';
        
        foreach ($admins as $admin) {
            $username = $admin['username'] ?? '?';
            $email = $admin['email'] ?? '?';
            $role = $admin['role'] ?? '?';
            $status = $admin['status'] ?? 'active';
            
            // Find which password field exists
            $pwd_field = 'NOT FOUND';
            $pwd_hash = '';
            if (isset($admin['password_hashed'])) {
                $pwd_field = 'password_hashed';
                $pwd_hash = substr($admin['password_hashed'], 0, 50) . '...';
            } elseif (isset($admin['password'])) {
                $pwd_field = 'password';
                $pwd_hash = substr($admin['password'], 0, 50) . '...';
            }
            
            echo '<tr>';
            echo '<td><code>' . $username . '</code></td>';
            echo '<td>' . $email . '</td>';
            echo '<td>' . $role . '</td>';
            echo '<td>' . $status . '</td>';
            echo '<td class="' . ($pwd_field == 'NOT FOUND' ? 'bad' : 'good') . '">' . $pwd_field . '</td>';
            echo '<td><code style="font-size:11px;">' . $pwd_hash . '</code></td>';
            echo '</tr>';
        }
        
        echo '</table>';
        
        echo '<h2>Next Steps:</h2>';
        echo '<ol>';
        echo '<li>Try logging in with username: <code>' . ($admins[0]['username'] ?? 'admin') . '</code></li>';
        echo '<li>If you forgot the password, I can help you reset it</li>';
        echo '<li>If you don\'t have an admin user, create one first</li>';
        echo '</ol>';
    }
    
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="box">';
    echo '<p class="bad">❌ Error: ' . $e->getMessage() . '</p>';
    echo '</div>';
}

?>

<div class="box">
    <h2>⚡ Quick Test</h2>
    <form method="POST">
        <p>Test a password here:</p>
        <p>
            <label>Username: <input type="text" name="test_username" value="admin"></label>
        </p>
        <p>
            <label>Password: <input type="password" name="test_password"></label>
        </p>
        <p>
            <button type="submit" name="test_btn">Test Login</button>
        </p>
    </form>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_btn'])) {
        global $admins_collection;
        
        $test_username = $_POST['test_username'] ?? '';
        $test_password = $_POST['test_password'] ?? '';
        
        $admin = $admins_collection->findOne(['username' => $test_username]);
        
        if (!$admin) {
            echo '<p class="bad">User not found: ' . $test_username . '</p>';
        } else {
            $pwd_field = isset($admin['password_hashed']) ? 'password_hashed' : 'password';
            $stored_hash = $admin[$pwd_field] ?? null;
            
            if (!$stored_hash) {
                echo '<p class="bad">No password hash found!</p>';
            } else {
                $verified = verifyPassword($test_password, $stored_hash);
                if ($verified) {
                    echo '<p class="good">✅ Password is CORRECT!</p>';
                } else {
                    echo '<p class="bad">❌ Password is WRONG!</p>';
                }
            }
        }
    }
    ?>
</div>

</body>
</html>
