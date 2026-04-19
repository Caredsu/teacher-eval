<?php
/**
 * Admin Login Page - Enhanced Version with UX Improvements
 * Improvements:
 * - Show/hide password toggle
 * - Better mobile role selector
 * - Optimized input spacing
 * - Form validation
 * - Better accessibility
 */

require_once '../includes/helpers.php';

initializeSession();
if (isLoggedIn()) {
    echo '<script>window.location.href="' . (isset($_SERVER['HTTP_HOST']) ? 'https://' . $_SERVER['HTTP_HOST'] : 'http://localhost') . '/teacher-eval/admin/dashboard.php";</script>';
    exit;
}

require_once '../config/database.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Security token invalid. Please try again.';
    } else {
        $username = getPOST('username');
        $password = getPOST('password');
        $selected_role = getPOST('login_role') ?? 'admin';
        
        if (empty($username) || empty($password)) {
            $error = 'Username and password are required.';
        } else {
            try {
                global $admins_collection;
                $admin = $admins_collection->findOne(['username' => $username]);
                
                $password_field = isset($admin['password_hashed']) ? 'password_hashed' : 'password';
                $stored_hash = $admin[$password_field] ?? null;
                
                if ($admin && $stored_hash && verifyPassword($password, $stored_hash)) {
                    $user_status = $admin['status'] ?? 'active';
                    if ($user_status !== 'active') {
                        $error = 'Your account has been deactivated. Please contact an administrator.';
                        logActivity('LOGIN_FAILED', 'Inactive user attempted login: ' . $username);
                    } else {
                        $user_role = $admin['role'] ?? 'admin';
                        
                        if ($user_role !== $selected_role) {
                            $error = "This account is registered as " . ucfirst($user_role) . ". Please select the correct role.";
                            logActivity('LOGIN_FAILED', 'Role mismatch for user: ' . $username);
                        } else {
                            $_SESSION['admin_id'] = (string) $admin['_id'];
                            $_SESSION['admin_username'] = $admin['username'];
                            $_SESSION['admin_role'] = $user_role;
                            $_SESSION['just_logged_in'] = true;
                            
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
                            redirect(BASE_URL . '/admin/dashboard.php');
                        }
                    }
                } else {
                    $error = 'Invalid username or password.';
                    logActivity('LOGIN_FAILED', 'Failed login attempt with username: ' . $username);
                }
            } catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
                $error = 'Database connection timeout. MongoDB server is not responding. Please try again in a moment.';
            } catch (\MongoDB\Driver\Exception\ServerSelectionTimeoutException $e) {
                $error = 'Database server is unavailable. Please try again later.';
            } catch (\Exception $e) {
                $error = 'Database error. Please try again.';
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
    <meta name="theme-color" content="#3B82F6">
    <title>Admin Login - Fullbright College Inc.</title>
    
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="<?= ASSETS_URL ?>/css/dark-theme.css" as="style">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dark-theme.css">
    
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
            background-color: #1e293b;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 50%, rgba(59, 130, 246, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(139, 92, 246, 0.1) 0%, transparent 50%),
                url('<?= ASSETS_URL ?>/img/1.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            z-index: -2;
            pointer-events: none;
        }

        body::after {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(30, 41, 59, 0.85);
            z-index: -1;
            pointer-events: none;
        }

        .login-wrapper {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            z-index: 1;
            animation: fadeIn 0.6s ease-out;
            position: relative;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-30px); }
            to { opacity: 1; transform: translateX(0); }
        }

        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(30px); }
            to { opacity: 1; transform: translateX(0); }
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
            background: linear-gradient(135deg, #ffffff 0%, #e0f2fe 50%, #bfdbfe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 15px;
            line-height: 1.2;
            filter: drop-shadow(0 4px 12px rgba(59, 130, 246, 0.3));
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
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 16px;
            padding: 45px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 40px rgba(59, 130, 246, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.25);
            position: relative;
            transition: all 0.3s ease;
        }

        .login-form-container:hover {
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 60px rgba(59, 130, 246, 0.25);
            border-color: rgba(255, 255, 255, 0.35);
        }

        .form-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .role-selector {
            display: flex;
            gap: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 8px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            z-index: 100;
            margin-bottom: 20px;
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
            flex: 1;
        }

        .role-btn:hover {
            color: white;
            border-color: rgba(255, 255, 255, 0.5);
        }

        .role-btn:focus-visible {
            outline: 2px solid #3B82F6;
            outline-offset: 2px;
        }

        .role-btn.active {
            background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
            border-color: #3B82F6;
            color: white;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .form-header .icon {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            color: white;
        }

        .form-header h2 {
            color: #ffffff;
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
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-danger {
            background: rgba(220, 38, 38, 0.15);
            border-left: 4px solid rgba(220, 38, 38, 0.6);
            color: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
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

        .input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .form-control {
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            padding: 14px 16px 14px 45px;
            font-size: 15px;
            transition: all 0.3s ease;
            width: 100%;
            background-color: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            position: relative;
        }

        .form-control:focus {
            outline: none;
            border-color: rgba(59, 130, 246, 0.8);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.3), inset 0 0 10px rgba(59, 130, 246, 0.1);
            background-color: rgba(255, 255, 255, 0.25);
        }

        .form-control:focus-visible {
            outline: 2px solid #3B82F6;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #ffffff;
            font-size: 20px;
            pointer-events: none;
            z-index: 10;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.6));
        }

        .input-addon-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #1a1a1a;
            cursor: pointer;
            font-size: 18px;
            padding: 6px;
            border-radius: 6px;
            transition: all 0.3s ease;
            z-index: 11;
        }

        .input-addon-btn:hover {
            color: #000000;
            background: rgba(255, 255, 255, 0.2);
        }

        .input-addon-btn:focus-visible {
            outline: 2px solid #3B82F6;
            outline-offset: -1px;
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
            border: 2px solid rgba(255, 255, 255, 0.4);
            border-radius: 6px;
            cursor: pointer;
            accent-color: #3B82F6;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .form-check-input:focus-visible {
            outline: 2px solid #3B82F6;
            outline-offset: 2px;
        }

        .form-check-input:checked {
            background-color: #3B82F6;
            border-color: #3B82F6;
        }

        .form-check-label {
            color: rgba(255, 255, 255, 0.95);
            font-size: 14px;
            cursor: pointer;
            margin: 0;
            font-weight: 500;
        }

        .forgot-password {
            text-align: right;
            margin-bottom: 25px;
        }

        .forgot-password a {
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .forgot-password a:hover {
            color: #3B82F6;
            text-decoration: underline;
        }

        .forgot-password a:focus-visible {
            outline: 2px solid #3B82F6;
            outline-offset: 2px;
            border-radius: 4px;
        }

        .btn-login {
            width: 100%;
            background: linear-gradient(135deg, #1E3A8A 0%, #3B82F6 100%);
            border: none;
            color: white;
            padding: 16px;
            font-weight: 700;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover:not(:disabled)::before {
            left: 100%;
        }

        .btn-login:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.5), 0 0 30px rgba(59, 130, 246, 0.3);
        }

        .btn-login:focus-visible {
            outline: 2px solid rgba(255, 255, 255, 0.8);
            outline-offset: 2px;
        }

        .btn-login:active:not(:disabled) {
            transform: translateY(-1px);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

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
            border-top-color: #3B82F6;
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

        /* RESPONSIVE DESIGN */
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

            .role-selector {
                position: relative !important;
                margin-bottom: 20px;
                width: 100%;
            }

            .role-btn {
                flex: 1;
                padding: 10px 16px;
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {
            .login-wrapper {
                padding: 15px;
            }

            .login-info h1 {
                font-size: 24px;
                margin-bottom: 20px;
            }

            .login-form-container {
                padding: 25px 15px;
                border-radius: 16px;
                box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            }

            .form-header h2 {
                font-size: 22px;
            }

            .form-header .icon {
                width: 50px;
                height: 50px;
                font-size: 28px;
            }

            .form-control {
                padding: 12px 12px 12px 42px;
                font-size: 16px;
            }

            .input-icon {
                font-size: 18px;
                left: 12px;
            }

            .input-addon-btn {
                right: 8px;
                font-size: 16px;
            }

            .features-list li {
                font-size: 14px;
                margin-bottom: 15px;
            }

            .features-list i {
                font-size: 20px;
            }

            .role-selector {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                margin-bottom: 20px;
            }

            .role-btn {
                padding: 10px 12px;
                font-size: 11px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation: none !important;
                transition: none !important;
            }
        }
    </style>
    
    <!-- SweetAlert2 for toast notifications -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
</head>
<body>
    <div class="login-wrapper">
        <div class="login-content">
            <!-- Left: Features -->
            <div class="login-info">
                <div style="margin-bottom: 35px; text-align: center;">
                    <img src="../assets/img/2.png" alt="Fullbright College Inc Logo" style="max-height: 80px; width: auto; margin-bottom: 15px; filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));">
                    <p style="color: rgba(255, 255, 255, 0.95); font-size: 16px; font-weight: 700; letter-spacing: 0.5px; margin: 0;">
                        Fullbright College Inc.
                    </p>
                    <p style="color: rgba(255, 255, 255, 0.75); font-size: 12px; font-weight: 500; margin: 6px 0 0 0;">Excellence in Education</p>
                </div>
                <h1>Teacher Evaluation System</h1>
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

            <!-- Right: Form -->
            <div class="login-form-container">
                <!-- Role Selector (Mobile-Friendly) -->
                <div class="role-selector" id="roleSelector" role="radiogroup" aria-label="Login role selection">
                    <button type="button" class="role-btn active" data-role="admin" aria-label="Login as Admin" aria-pressed="true">
                        <i class="bi bi-shield-lock"></i> Admin
                    </button>
                    <button type="button" class="role-btn" data-role="staff" aria-label="Login as Staff" aria-pressed="false">
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
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-circle" style="margin-right: 8px;"></i>
                        <strong>Login Error!</strong><br>
                        <?= escapeOutput($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" novalidate id="loginForm">
                    <?php outputCSRFToken(); ?>
                    <input type="hidden" name="login_role" id="login_role" value="admin">

                    <!-- Username -->
                    <div class="form-group">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-wrapper">
                            <i class="bi bi-person-fill input-icon"></i>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="username" 
                                name="username" 
                                placeholder="Enter your username"
                                required
                                autofocus
                                aria-label="Username"
                                aria-required="true"
                            >
                        </div>
                    </div>

                    <!-- Password with Show/Hide -->
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-wrapper">
                            <i class="bi bi-lock-fill input-icon"></i>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="password" 
                                name="password" 
                                placeholder="Enter your password"
                                required
                                aria-label="Password"
                                aria-required="true"
                            >
                            <button 
                                type="button" 
                                class="input-addon-btn" 
                                id="togglePassword" 
                                aria-label="Show password"
                                aria-controls="password"
                                title="Toggle password visibility"
                            >
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
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

                    <!-- Forgot Password -->
                    <div class="forgot-password">
                        <a href="#" tabindex="0">Forgot your password?</a>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn-login" id="loginBtn">
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show/Hide Password Toggle
            const togglePasswordBtn = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');
            
            if (togglePasswordBtn && passwordInput) {
                // Hide toggle initially
                togglePasswordBtn.style.display = 'none';
                
                // Show/hide toggle based on input content
                passwordInput.addEventListener('input', function() {
                    togglePasswordBtn.style.display = this.value.length > 0 ? 'block' : 'none';
                });
                
                // Toggle password visibility
                togglePasswordBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const isPassword = passwordInput.type === 'password';
                    passwordInput.type = isPassword ? 'text' : 'password';
                    
                    // Update icon
                    this.innerHTML = isPassword 
                        ? '<i class="bi bi-eye-slash"></i>' 
                        : '<i class="bi bi-eye"></i>';
                    
                    // Update aria-label
                    this.setAttribute('aria-label', isPassword 
                        ? 'Hide password' 
                        : 'Show password');
                    
                    // Focus back on input
                    passwordInput.focus();
                });
            }

            // Remember Me Functionality
            const rememberCheckbox = document.getElementById('remember');
            const usernameInput = document.getElementById('username');
            
            // Load remembered username
            const savedUsername = localStorage.getItem('teacher_eval_username');
            if (savedUsername) {
                usernameInput.value = savedUsername;
                rememberCheckbox.checked = true;
            }

            // Save on form submit
            const loginForm = document.getElementById('loginForm');
            loginForm.addEventListener('submit', function() {
                if (rememberCheckbox.checked) {
                    localStorage.setItem('teacher_eval_username', usernameInput.value);
                } else {
                    localStorage.removeItem('teacher_eval_username');
                }
            });

            // Role Selector
            const roleButtons = document.querySelectorAll('.role-btn');
            const roleTitle = document.getElementById('roleTitle');
            const roleDescription = document.getElementById('roleDescription');
            const loginRoleInput = document.getElementById('login_role');

            roleButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const role = this.getAttribute('data-role');

                    roleButtons.forEach(btn => {
                        btn.classList.remove('active');
                        btn.setAttribute('aria-pressed', 'false');
                    });

                    this.classList.add('active');
                    this.setAttribute('aria-pressed', 'true');
                    loginRoleInput.value = role;

                    if (role === 'admin') {
                        roleTitle.textContent = 'Admin Access';
                        roleDescription.textContent = 'Sign in to manage evaluations';
                    } else if (role === 'staff') {
                        roleTitle.textContent = 'Staff Access';
                        roleDescription.textContent = 'Sign in to view results';
                    }
                });
            });

            // Form Submission with Loading Overlay
            loginForm.addEventListener('submit', function(e) {
                const currentError = document.querySelector('.alert-danger');
                
                if (!currentError) {
                    document.getElementById('loadingOverlay').classList.add('active');
                    document.getElementById('loginBtn').disabled = true;
                }
                
                setTimeout(() => {
                    const loadingOverlay = document.getElementById('loadingOverlay');
                    if (loadingOverlay.classList.contains('active')) {
                        loadingOverlay.classList.remove('active');
                        document.getElementById('loginBtn').disabled = false;
                    }
                }, 3000);
            });

            // Check for logout notification
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('logged_out') && urlParams.get('logged_out') === '1') {
                // Show logout success toast at top right
                setTimeout(function() {
                    Swal.fire({
                        position: 'top-end',
                        icon: 'success',
                        title: 'Logged Out Successfully',
                        text: 'You have been logged out of the system',
                        toast: true,
                        showConfirmButton: false,
                        timer: 3000,
                        timerProgressBar: true,
                        didOpen: (toast) => {
                            toast.addEventListener('mouseenter', Swal.stopTimer)
                            toast.addEventListener('mouseleave', Swal.resumeTimer)
                        }
                    });
                    
                    // Remove the logged_out parameter from URL
                    window.history.replaceState({}, document.title, window.location.pathname);
                }, 300);
            }

            // Service Worker Registration
            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/teacher-eval/assets/js/admin-service-worker.js')
                    .then(registration => console.log('Service Worker registered'))
                    .catch(err => console.log('Service Worker registration failed:', err));
            }
        });
    </script>
</body>
</html>
