<?php
/**
 * Admin Login Page
 */

require_once '../includes/helpers.php';
require_once '../config/database.php';

initializeSession();

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    redirect('/teacher-eval/admin/dashboard.php');
}

$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Security token invalid. Please try again.';
    } else {
        $username = getPOST('username');
        $password = getPOST('password');
        $selected_role = getPOST('login_role') ?? 'admin';
        
        // Validate input
        if (empty($username) || empty($password)) {
            $error = 'Username and password are required.';
        } else {
            try {
                // Query admin in MongoDB
                $admin = $admins_collection->findOne(['username' => $username]);
                
                if ($admin && verifyPassword($password, $admin['password'])) {
                    // Check if user is active
                    $user_status = $admin['status'] ?? 'active';
                    if ($user_status !== 'active') {
                        $error = 'Your account has been deactivated. Please contact an administrator.';
                        logActivity('LOGIN_FAILED', 'Inactive user attempted login: ' . $username);
                    } else {
                        // Check if user role matches selected role
                        $user_role = $admin['role'] ?? 'admin';
                        
                        if ($user_role !== $selected_role) {
                            // User exists but role doesn't match
                            $error = "This account is registered as " . ucfirst($user_role) . ". Please select the correct role.";
                            logActivity('LOGIN_FAILED', 'Role mismatch for user: ' . $username . ' (selected: ' . $selected_role . ', actual: ' . $user_role . ')');
                        } else {
                            // Login successful
                            $_SESSION['admin_id'] = (string) $admin['_id'];
                            $_SESSION['admin_username'] = $admin['username'];
                            $_SESSION['admin_role'] = $user_role;
                            $_SESSION['just_logged_in'] = true;
                            
                            // Update last_login in database
                            global $admins_collection;
                            try {
                                $admins_collection->updateOne(
                                    ['_id' => $admin['_id']],
                                    ['$set' => ['last_login' => new MongoDB\BSON\UTCDateTime()]]
                                );
                            } catch (Exception $e) {
                                error_log('Failed to update last_login: ' . $e->getMessage());
                            }
                            
                            logActivity('LOGIN', 'Admin logged in');
                            redirect('/teacher-eval/admin/dashboard.php');
                        }
                    }
                } else {
                    // Invalid credentials
                    $error = 'Invalid username or password.';
                    logActivity('LOGIN_FAILED', 'Failed login attempt with username: ' . $username);
                }
            } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
                $error = 'Database connection timeout. MongoDB server is not responding. Please try again in a moment.';
            } catch (\MongoDB\Driver\Exception\ServerSelectionTimeoutException $e) {
                $error = 'Database server is unavailable. Please try again later.';
            } catch (\Exception $e) {
                $error = 'Database error. Please try again.';
                // Log the actual error for debugging
                error_log('Login DB Error: ' . $e->getMessage());
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
    <meta name="description" content="Fullbright College Inc. - Teacher Evaluation System">
    <meta name="theme-color" content="#667eea">
    <title>Admin Login - Fullbright College Inc.</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/teacher-eval/assets/css/dark-theme.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        body::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            pointer-events: none;
            z-index: -1;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            z-index: 1;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        .login-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }

        .login-info {
            animation: slideInLeft 0.8s ease-out;
        }

        .login-info h1 {
            font-size: clamp(32px, 5vw, 48px);
            font-weight: 800;
            color: white;
            margin-bottom: 15px;
            line-height: 1.2;
        }

        .login-info .subtitle {
            font-size: clamp(16px, 2vw, 20px);
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 40px;
            line-height: 1.5;
        }

        .features-list {
            list-style: none;
            margin-bottom: 40px;
        }

        .features-list li {
            display: flex;
            align-items: flex-start;
            gap: 15px;
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.95);
            font-size: 15px;
            line-height: 1.6;
        }

        .features-list i {
            font-size: 24px;
            color: #ffd700;
            flex-shrink: 0;
            margin-top: 2px;
            animation: bounce 2s ease-in-out infinite;
        }

        .login-form-container {
            animation: slideInRight 0.8s ease-out;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 50px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .role-selector {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 100;
        }

        .role-btn {
            padding: 8px 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            background: transparent;
            color: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.3s ease;
        }

        .role-btn:hover {
            color: white;
            border-color: rgba(255, 255, 255, 0.5);
        }

        .role-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .form-header .icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
        }

        .form-header h2 {
            color: white;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .form-header p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
        }

        .alert {
            margin-bottom: 25px;
            border: none;
            border-radius: 12px;
            padding: 16px;
            animation: slideIn 0.4s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-danger {
            background: linear-gradient(135deg, #fee 0%, #fdd 100%);
            border-left: 4px solid #dc3545;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 10px;
            font-size: 14px;
        }

        .form-control {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            padding: 14px 16px;
            font-size: 15px;
            transition: all 0.3s ease;
            width: 100%;
            background-color: rgba(255, 255, 255, 0.95);
            color: #333;
        }

        .form-control:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.8);
            box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.2);
            background-color: white;
        }

        .form-control::placeholder {
            color: #999;
        }

        .form-check {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-check-input {
            width: 20px;
            height: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            cursor: pointer;
            accent-color: #667eea;
            transition: all 0.3s ease;
        }

        .form-check-input:checked {
            background-color: #667eea;
            border-color: #667eea;
        }

        .form-check-label {
            color: rgba(255, 255, 255, 0.95);
            font-size: 14px;
            cursor: pointer;
            margin: 0;
            font-weight: 500;
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            color: white;
            padding: 16px;
            font-weight: 700;
            border-radius: 12px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .btn-login:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.5);
        }

        .btn-login:active:not(:disabled) {
            transform: translateY(0);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Loading Overlay */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            backdrop-filter: blur(3px);
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-container {
            text-align: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 40px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(15px);
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top-color: #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 20px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-text {
            color: white;
            font-size: 18px;
            font-weight: 600;
        }

        .loading-subtext {
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
            margin-top: 8px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .login-content {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .login-info {
                text-align: center;
            }

            .login-info h1 {
                font-size: 32px;
            }

            .login-form-container {
                padding: 30px 20px;
            }

            .features-list li {
                justify-content: center;
            }

            .login-info .subtitle {
                font-size: 16px;
            }

            body {
                padding: 20px 0;
            }

            .login-wrapper {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .login-info h1 {
                font-size: 24px;
                margin-bottom: 20px;
            }

            .login-form-container {
                padding: 25px 15px;
                border-radius: 16px;
            }

            .form-header h2 {
                font-size: 22px;
            }

            .form-header .icon {
                width: 50px;
                height: 50px;
                font-size: 28px;
            }

            .features-list {
                margin-bottom: 30px;
            }

            .features-list li {
                font-size: 14px;
                margin-bottom: 15px;
            }

            .features-list i {
                font-size: 20px;
            }

            .role-selector {
                position: absolute;
                top: 15px;
                right: 15px;
                width: auto;
            }

            .role-btn {
                font-size: 11px;
                padding: 6px 12px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-content">
            <!-- Left Side: Features & Info -->
            <div class="login-info">
                <div style="margin-bottom: 35px; text-align: center;">
                    <img src="../assets/img/2.png" alt="Fullbright College Inc Logo" style="max-height: 80px; width: auto; margin-bottom: 15px; filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));">
                    <p style="color: rgba(255, 255, 255, 0.95); font-size: 16px; font-weight: 700; letter-spacing: 0.5px; margin: 0;">
                        Fullbright College Inc.
                    </p>
                    <p style="color: rgba(255, 255, 255, 0.75); font-size: 12px; font-weight: 500; margin: 6px 0 0 0;">Excellence in Education</p>
                </div>
                <h1> Teacher Evaluation System</h1>
                <p class="subtitle">Streamline feedback collection and improve teaching quality</p>
                
                <ul class="features-list">
                    <li>
                        <i class="bi bi-shield-check"></i>
                        <div>
                            <strong>Secure & Private</strong><br>
                            <span style="font-size: 13px; opacity: 0.9;">Anonymous evaluations with end-to-end protection</span>
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-lightning-fill"></i>
                        <div>
                            <strong>Real-time Analytics</strong><br>
                            <span style="font-size: 13px; opacity: 0.9;">Instant insights and detailed performance metrics</span>
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-graph-up"></i>
                        <div>
                            <strong>Data-Driven Insights</strong><br>
                            <span style="font-size: 13px; opacity: 0.9;">Visualize trends and identify improvement areas</span>
                        </div>
                    </li>
                    <li>
                        <i class="bi bi-phone"></i>
                        <div>
                            <strong>Mobile Friendly</strong><br>
                            <span style="font-size: 13px; opacity: 0.9;">Works seamlessly on all devices</span>
                        </div>
                    </li>
                </ul>

                <div style="background: rgba(255, 255, 255, 0.15); border-radius: 12px; padding: 20px; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.2);">
                    <p style="color: rgba(255, 255, 255, 0.95); font-size: 14px; margin: 0;">
                        <i class="bi bi-info-circle" style="margin-right: 8px;"></i>
                        Trusted by educational institutions for reliable feedback collection
                    </p>
                </div>
            </div>

            <!-- Right Side: Login Form -->
            <div class="login-form-container">
                <!-- Role Selector -->
                <div class="role-selector" id="roleSelector">
                    <button type="button" class="role-btn active" data-role="admin" aria-label="Login as Admin">
                        <i class="bi bi-shield-lock"></i> Admin
                    </button>
                    <button type="button" class="role-btn" data-role="staff" aria-label="Login as Staff">
                        <i class="bi bi-person-badge"></i> Staff
                    </button>
                </div>

                <div class="form-header">
                    <div class="icon">
                        <i class="bi bi-person-lock"></i>
                    </div>
                    <h2 id="roleTitle">Admin Access</h2>
                    <p id="roleDescription">Sign in to manage evaluations</p>
                </div>

                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle" style="margin-right: 8px;"></i>
                        <strong>Login Error!</strong><br>
                        <?= escapeOutput($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <?php outputCSRFToken(); ?>
                    <input type="hidden" name="login_role" id="login_role" value="admin">

                    <!-- Username -->
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="bi bi-person" style="margin-right: 6px;"></i>Username
                        </label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="username" 
                            name="username" 
                            placeholder="Enter your username"
                            required
                            autofocus
                            aria-label="Username"
                        >
                    </div>

                    <!-- Password -->
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="bi bi-lock" style="margin-right: 6px;"></i>Password
                        </label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password"
                            required
                            aria-label="Password"
                        >
                    </div>

                    <!-- Remember Me -->
                    <div class="form-check">
                        <input 
                            type="checkbox" 
                            class="form-check-input" 
                            id="remember" 
                            name="remember"
                        >
                        <label class="form-check-label" for="remember">
                            Remember me for 30 days
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-login">
                        <i class="bi bi-box-arrow-in-right" style="margin-right: 8px;"></i>
                        Sign In
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-container">
            <div class="spinner"></div>
            <div class="loading-text">Signing In...</div>
            <div class="loading-subtext">Redirecting to dashboard</div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some interactivity
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('.form-control');
            
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });

            // Autofill effect
            const usernameInput = document.getElementById('username');
            if (usernameInput) {
                usernameInput.addEventListener('change', function() {
                    if (this.value === 'admin') {
                        document.getElementById('password').focus();
                    }
                });
            }

            // Role selector functionality
            const roleButtons = document.querySelectorAll('.role-btn');
            const roleTitle = document.getElementById('roleTitle');
            const roleDescription = document.getElementById('roleDescription');

            roleButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const role = this.getAttribute('data-role');

                    // Remove active class from all buttons
                    roleButtons.forEach(btn => btn.classList.remove('active'));

                    // Add active class to clicked button
                    this.classList.add('active');

                    // Update hidden input with selected role
                    document.getElementById('login_role').value = role;

                    // Update title and description
                    if (role === 'admin') {
                        roleTitle.textContent = 'Admin Access';
                        roleDescription.textContent = 'Sign in to manage evaluations';
                    } else if (role === 'staff') {
                        roleTitle.textContent = 'Staff Access';
                        roleDescription.textContent = 'Sign in to view results';
                    }
                });
            });

            // Form submission with loading
            const loginForm = document.querySelector('form');
            if (loginForm) {
                loginForm.addEventListener('submit', function(e) {
                    // Check if there's an error message on current page load
                    const currentError = document.querySelector('.alert-danger');
                    
                    // Only show spinner if NO error is visible
                    if (!currentError) {
                        document.getElementById('loadingOverlay').classList.add('active');
                    }
                    
                    // Safety timeout: if page hasn't redirected after 3 seconds, hide spinner
                    setTimeout(() => {
                        const loadingOverlay = document.getElementById('loadingOverlay');
                        if (loadingOverlay.classList.contains('active')) {
                            loadingOverlay.classList.remove('active');
                        }
                    }, 3000);
                });
            }
        });
    </script>
</body>
</html>

