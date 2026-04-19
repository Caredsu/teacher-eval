<?php
/**
 * Ultra-Fast Admin Login Page
 * Optimized for maximum speed (minimal dependencies)
 */

require_once '../includes/helpers.php';

initializeSession();
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

require_once '../config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Security token invalid.';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            $error = 'Username and password required.';
        } else {
            try {
                global $admins_collection;
                $admin = $admins_collection->findOne(['username' => $username]);
                
                if ($admin && verifyPassword($password, $admin['password'] ?? $admin['password_hashed'] ?? '')) {
                    $_SESSION['admin_id'] = (string)$admin['_id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
                    
                    // Fast redirect
                    echo '<script>window.location.href="dashboard.php";</script>';
                    exit;
                } else {
                    $error = 'Invalid credentials.';
                }
            } catch (Exception $e) {
                $error = 'Connection error.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    
    <!-- CRITICAL CSS INLINED - NO RENDER BLOCKING -->
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { font-size: 16px; -webkit-font-smoothing: antialiased; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-box {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
            padding: 40px;
            animation: slideIn 0.6s ease-out;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
        }
        
        h1 {
            font-size: 24px;
            color: #1f2937;
            margin-bottom: 5px;
        }
        
        .subtitle {
            color: #6b7280;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #374151;
            font-weight: 600;
            font-size: 14px;
        }
        
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            transition: border 0.2s;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.1s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #7f1d1d;
            border-left: 4px solid #dc2626;
        }
        
        .loading {
            display: none;
            text-align: center;
            color: #667eea;
            font-size: 14px;
            margin-top: 15px;
        }
        
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #e5e7eb;
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @media (max-width: 480px) {
            .login-box { padding: 30px 20px; }
            h1 { font-size: 20px; }
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="login-header">
            <div class="logo">👤</div>
            <h1>Admin Access</h1>
            <p class="subtitle">Teacher Evaluation System</p>
        </div>
        
        <?php if (!empty($error)): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <form id="loginForm" method="POST">
            <?php outputCSRFToken(); ?>
            
            <div class="form-group">
                <label for="username">Username</label>
                <input 
                    type="text" 
                    id="username" 
                    name="username" 
                    placeholder="admin" 
                    required 
                    autofocus
                    autocomplete="username"
                >
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="••••••••" 
                    required
                    autocomplete="current-password"
                >
            </div>
            
            <button type="submit" class="btn-login">Sign In</button>
            
            <div class="loading" id="loading">
                <span class="spinner"></span> Signing in...
            </div>
        </form>
    </div>
    
    <!-- MINIMAL JAVASCRIPT - NO FRAMEWORKS -->
    <script>
        // No external dependencies needed
        const form = document.getElementById('loginForm');
        const loading = document.getElementById('loading');
        
        form.addEventListener('submit', function() {
            // Only show loading if no error visible
            if (!document.querySelector('.alert-error')) {
                loading.style.display = 'block';
            }
            
            // Auto-hide after 3 seconds (page should have redirected)
            setTimeout(() => {
                loading.style.display = 'none';
            }, 3000);
        });
        
        // Register service worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('../assets/js/admin-service-worker.js').catch(() => {});
        }
    </script>
</body>
</html>
