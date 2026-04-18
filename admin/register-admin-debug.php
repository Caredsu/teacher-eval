<?php
require_once '../config/database.php';
header('Content-Type: text/plain');
echo 'DB_HOST: ' . DB_HOST . PHP_EOL;
echo 'DB_NAME: ' . DB_NAME . PHP_EOL;
exit;

// Simple one-time admin registration form for MongoDB Atlas
require_once '../config/database.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = 'admin';
    $status = 'active';
    $now = new MongoDB\BSON\UTCDateTime();

    if ($username && $email && $password) {
        // Check if username exists
        $exists = $admins_collection->findOne(['username' => $username]);
        if ($exists) {
            $message = 'Username already exists!';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $admins_collection->insertOne([
                'username' => $username,
                'email' => $email,
                'password' => $hash,
                'role' => $role,
                'status' => $status,
                'created_at' => $now,
                'created_by' => 'register_form',
                'updated_at' => $now,
                'updated_by' => 'register_form',
                'last_login' => null
            ]);
            $message = 'Admin registered! You can now login.';
        }
    } else {
        $message = 'All fields are required.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Admin</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f3f3f3; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .register-box { background: #fff; padding: 32px 24px; border-radius: 10px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); min-width: 320px; }
        .register-box h2 { margin-bottom: 18px; }
        .register-box input { width: 100%; padding: 10px; margin-bottom: 14px; border-radius: 6px; border: 1px solid #bbb; }
        .register-box button { width: 100%; padding: 10px; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer; }
        .msg { margin-bottom: 12px; color: #b91c1c; font-weight: bold; }
        .msg.success { color: #15803d; }
    </style>
</head>
<body>
    <div class="register-box">
        <h2>Register Admin</h2>
        <?php if ($message): ?>
            <div class="msg<?= $message === 'Admin registered! You can now login.' ? ' success' : '' ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Register Admin</button>
        </form>
    </div>
</body>
</html>
