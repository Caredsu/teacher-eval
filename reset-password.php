<?php
/**
 * Admin Password Reset Utility
 * Use this to reset admin passwords
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

// IMPORTANT: Restrict access - only run from terminal or localhost
$is_localhost = in_array($_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) || PHP_SAPI === 'cli';
$is_cli = PHP_SAPI === 'cli';

if (!$is_localhost && !$is_cli) {
    die('Access denied. This script can only be run from localhost.');
}

if ($is_cli) {
    // CLI Mode
    if (count($argv) < 3) {
        echo "Usage: php reset-password.php <username> <new_password>\n";
        echo "Example: php reset-password.php admin admin123\n";
        exit(1);
    }
    
    $username = $argv[1];
    $new_password = $argv[2];
} else {
    // Web Mode
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Reset Admin Password</title>
            <style>
                body { font-family: Arial; background: #1a1e2e; color: #e0e0e0; padding: 40px; }
                .container { max-width: 400px; margin: 50px auto; background: #16213e; padding: 30px; border-radius: 8px; }
                h1 { color: #00d4ff; }
                input { width: 100%; padding: 10px; margin: 10px 0; background: #0f3460; color: #e0e0e0; border: 1px solid #00d4ff; border-radius: 4px; }
                button { width: 100%; padding: 10px; background: #00d4ff; color: #1a1e2e; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
                button:hover { background: #00b8d4; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>🔐 Reset Admin Password</h1>
                <form method="POST">
                    <label>Username:</label>
                    <input type="text" name="username" placeholder="admin" required>
                    
                    <label>New Password:</label>
                    <input type="password" name="password" placeholder="Enter new password" required>
                    
                    <label>Confirm Password:</label>
                    <input type="password" name="password_confirm" placeholder="Confirm password" required>
                    
                    <button type="submit">Reset Password</button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    $username = $_POST['username'] ?? '';
    $new_password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if ($new_password !== $password_confirm) {
        die('❌ Passwords do not match!');
    }
}

// Reset the password
try {
    if (empty($username) || empty($new_password)) {
        die('❌ Username and password are required!');
    }
    
    // Hash the new password
    $hashed_password = hashPassword($new_password);
    
    // Update in MongoDB
    $result = $admins_collection->updateOne(
        ['username' => $username],
        ['$set' => ['password' => $hashed_password, 'updated_at' => new DateTime()]]
    );
    
    if ($result->getModifiedCount() > 0) {
        $message = "✅ Password reset successfully for user: " . htmlspecialchars($username);
        if (!$is_cli) {
            echo "<div style='background: #1a1e2e; color: #00d4ff; padding: 20px; border-radius: 8px; text-align: center; font-size: 18px;'>$message<pre>New credentials:\nUsername: " . htmlspecialchars($username) . "\nPassword: " . htmlspecialchars($new_password) . "</pre></div>";
        } else {
            echo $message . "\n";
            echo "New credentials:\n";
            echo "Username: $username\n";
            echo "Password: $new_password\n";
        }
    } else {
        $message = "❌ User not found: " . htmlspecialchars($username);
        if (!$is_cli) {
            echo "<div style='background: #1a1e2e; color: #f44; padding: 20px; border-radius: 8px; text-align: center;'>$message</div>";
        } else {
            echo $message . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
