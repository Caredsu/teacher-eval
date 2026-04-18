<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);

try {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/includes/helpers.php';
} catch (Exception $e) {
    die("Connection error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Admin Account</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 100%;
        }
        h1 { 
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.5);
        }
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #764ba2;
        }
        .message {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>📝 Create Admin Account</h1>

    <?php
    $success = '';
    $error = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? 'admin';

        // Validation
        if (empty($username) || empty($email) || empty($password)) {
            $error = 'All fields are required!';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters!';
        } else {
            try {
                global $admins_collection;

                // Check if username already exists
                $existing = $admins_collection->findOne(['username' => $username]);
                if ($existing) {
                    $error = 'Username already exists!';
                } else {
                    // Create new admin
                    $hashed_password = hashPassword($password);
                    
                    try {
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
                            $success = "✅ Admin account created successfully!\n\nUsername: $username\nEmail: $email\nRole: $role\n\nYou can now login at: http://localhost/teacher-eval/admin/login.php";
                        } else {
                            $error = 'Failed to create account. Try again.';
                        }
                    } catch (Exception $e) {
                        $error = 'Database error: ' . $e->getMessage();
                    }
                }
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }

    if ($success) {
        echo '<div class="message success"><pre>' . htmlspecialchars($success) . '</pre></div>';
    }
    if ($error) {
        echo '<div class="message error">' . htmlspecialchars($error) . '</div>';
    }
    ?>

    <form method="POST">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" placeholder="Enter username" required value="<?php echo $_POST['username'] ?? ''; ?>">
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" placeholder="Enter email" required value="<?php echo $_POST['email'] ?? ''; ?>">
        </div>

        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="Enter password (min 6 chars)" required>
        </div>

        <div class="form-group">
            <label for="role">Role:</label>
            <select id="role" name="role">
                <option value="admin">Admin</option>
                <option value="staff">Staff</option>
            </select>
        </div>

        <button type="submit">Create Admin Account</button>
    </form>

    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; text-align: center; color: #666; font-size: 12px;">
        <p>After creating, go to the login page:</p>
        <a href="/teacher-eval/admin/login.php" style="color: #667eea; text-decoration: none;">http://localhost/teacher-eval/admin/login.php</a>
    </div>
</div>

</body>
</html>
