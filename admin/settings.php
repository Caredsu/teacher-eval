<?php
/**
 * Admin Settings Page
 */

require_once '../includes/helpers.php';
require_once '../config/database.php';

initializeSession();
requireLogin();

$success_msg = getSuccessMessage();
$error_msg = getErrorMessage();

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken(getPOST('csrf_token'))) {
        setErrorMessage('Security token invalid.');
    } elseif (isset($_POST['update_evaluation_status'])) {
        // Handle evaluation status toggle (admin only)
        if (!isAdmin()) {
            setErrorMessage('Access denied. Only administrators can change evaluation status.');
        } else {
            try {
                $evaluation_status = getPOST('evaluation_status') === 'on' ? 'on' : 'off';
            
            // Upsert system settings
            $settings_collection->updateOne(
                ['_id' => 'evaluation_settings'],
                [
                    '$set' => [
                        'status' => $evaluation_status,
                        'updated_at' => new MongoDB\BSON\UTCDateTime(),
                        'updated_by' => getLoggedInAdminUsername()
                    ]
                ],
                ['upsert' => true]
            );
            
            setSuccessMessage('Evaluation status updated to: ' . strtoupper($evaluation_status));
            logActivity('EVALUATION_STATUS_CHANGED', 'Evaluation status changed to: ' . $evaluation_status);
        } catch (\Exception $e) {
            setErrorMessage('Error: ' . $e->getMessage());
        }
        }
    } else {
        // Password change (available for both admin and staff)
        $current_password = getPOST('current_password');
        $new_password = getPOST('new_password');
        $confirm_password = getPOST('confirm_password');
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            setErrorMessage('All fields are required.');
        } elseif ($new_password !== $confirm_password) {
            setErrorMessage('New passwords do not match.');
        } elseif (strlen($new_password) < 6) {
            setErrorMessage('Password must be at least 6 characters.');
        } else {
            try {
                $admin = $admins_collection->findOne(['_id' => new MongoDB\BSON\ObjectId(getLoggedInAdmin())]);
                
                if (!$admin || !verifyPassword($current_password, $admin['password'])) {
                    setErrorMessage('Current password is incorrect.');
                } else {
                    // Update password
                    $admins_collection->updateOne(
                        ['_id' => new MongoDB\BSON\ObjectId(getLoggedInAdmin())],
                        [
                            '$set' => [
                                'password' => hashPassword($new_password),
                                'updated_at' => new MongoDB\BSON\UTCDateTime()
                            ]
                        ]
                    );
                    
                    setSuccessMessage('Password changed successfully!');
                    logActivity('PASSWORD_CHANGED', 'Admin changed password');
                }
            } catch (\Exception $e) {
                setErrorMessage('Error: ' . $e->getMessage());
            }
        }
    }
    
    redirect(BASE_URL . '/admin/settings.php');
}

// Get admin info
$admin = $admins_collection->findOne(['_id' => new MongoDB\BSON\ObjectId(getLoggedInAdmin())]);

// Get current evaluation status
$eval_settings = $settings_collection->findOne(['_id' => 'evaluation_settings']);
$evaluation_status = $eval_settings['status'] ?? 'on';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Teacher Evaluation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dark-theme.css?v=2.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        /* Light theme sidebar styling */
        /* Sidebar light theme (always applied) */
        .list-group {
            background-color: #ffffff;
            border-color: #e2e8f0;
        }
        
        .list-group-item {
            background-color: #f8fafc;
            border-color: #e2e8f0;
            color: #000000;
            border-radius: 6px;
            margin-bottom: 6px;
            transition: all 0.2s ease;
        }
        
        .list-group-item:hover {
            background-color: #f1f5f9;
            color: #8b5cf6;
        }
        
        .list-group-item.active {
            background-color: #8b5cf6;
            border-color: #8b5cf6;
            color: white;
        }
        
        /* Card light theme (always applied) */
        .card {
            background-color: #ffffff;
            border-color: #e2e8f0;
            color: #000000;
        }
        
        .card-header {
            background-color: #f1f5f9 !important;
            border-color: #e2e8f0;
            color: #000000;
        }
        
        .card-body {
            background-color: #ffffff;
            color: #000000;
        }
        
        /* Form inputs light theme (always applied) */
        .form-control,
        .form-select {
            background-color: #ffffff;
            border-color: #cbd5e1;
            color: #000000;
        }
        
        .form-control:focus,
        .form-select:focus {
            background-color: #ffffff;
            color: #000000;
            border-color: #8b5cf6;
            box-shadow: 0 0 0 0.2rem rgba(139, 92, 246, 0.25);
        }
        
        .form-label {
            color: #000000;
        }
        
        .form-check-label {
            color: #000000;
        }
        
        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>
    
    <!-- Main Content Wrapper -->
    <div class="main-content">
        <div class="container-fluid py-5">
        <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="h2"><i class="bi bi-gear"></i> Settings</h1>
                <p class="text-muted">Manage your account preferences</p>
                <?php if (isStaff()): ?>
                    <small class="text-info"><i class="bi bi-info-circle"></i> Staff can update password only. Other settings are admin-exclusive.</small>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if (!empty($success_msg)): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Success!',
                        text: '<?= escapeOutput(addslashes($success_msg)) ?>',
                        icon: 'success',
                        confirmButtonColor: '#667eea',
                        allowOutsideClick: false
                    });
                });
            </script>
        <?php endif; ?>
        
        <?php if (!empty($error_msg)): ?>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        title: 'Error!',
                        text: '<?= escapeOutput(addslashes($error_msg)) ?>',
                        icon: 'error',
                        confirmButtonColor: '#667eea',
                        allowOutsideClick: false
                    });
                });
            </script>
        <?php endif; ?>
        
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="list-group">
                    <a href="#account" class="list-group-item list-group-item-action nav-link">
                        <i class="bi bi-person"></i> Account
                    </a>
                    <a href="#security" class="list-group-item list-group-item-action nav-link">
                        <i class="bi bi-shield-lock"></i> Security
                    </a>
                    <?php if (getUserRole() === 'admin'): ?>
                    <a href="#evaluation" class="list-group-item list-group-item-action nav-link">
                        <i class="bi bi-toggle-on"></i> Evaluations
                    </a>
                    <a href="#system" class="list-group-item list-group-item-action nav-link">
                        <i class="bi bi-info-circle"></i> System Info
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Account Settings -->
                <div class="card border-0 shadow-sm mb-4" id="account">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-person"></i> Account Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Username:</strong></p>
                                <p class="text-muted"><?= escapeOutput($admin['username'] ?? '') ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Email:</strong></p>
                                <p class="text-muted"><?= escapeOutput($admin['email'] ?? 'Not set') ?></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Role:</strong></p>
                                <p class="text-muted"><span class="badge bg-primary"><?= escapeOutput($admin['role'] ?? 'Admin') ?></span></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Member Since:</strong></p>
                                <p class="text-muted"><?= formatDateTime($admin['created_at'] ?? '') ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Security Settings -->
                <div class="card border-0 shadow-sm mb-4" id="security">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-shield-lock"></i> Change Password</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" data-confirm="Are you sure you want to change your password?">
                            <?php outputCSRFToken(); ?>
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password *</label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="current_password" 
                                    name="current_password"
                                    required
                                >
                                <small class="form-text text-muted">Enter your current password for verification</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password *</label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="new_password" 
                                    name="new_password"
                                    required
                                    minlength="6"
                                >
                                <small class="form-text text-muted">Minimum 6 characters</small>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password *</label>
                                <input 
                                    type="password" 
                                    class="form-control" 
                                    id="confirm_password" 
                                    name="confirm_password"
                                    required
                                    minlength="6"
                                >
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Password
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Evaluation Settings (Admin Only) -->
                <?php if (isAdmin()): ?>
                <div class="card border-0 shadow-sm mb-4" id="evaluation">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-toggle-on"></i> Evaluation Sessions</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3 text-muted">Control whether students can submit evaluations</p>
                        
                        <form method="POST">
                            <?php outputCSRFToken(); ?>
                            <input type="hidden" name="update_evaluation_status" value="1">
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        id="evaluation_status" 
                                        name="evaluation_status" 
                                        value="on"
                                        <?= $evaluation_status === 'on' ? 'checked' : '' ?>
                                        style="cursor: pointer; width: 50px; height: 25px;"
                                    >
                                    <label class="form-check-label" for="evaluation_status" style="cursor: pointer; font-size: 16px;">
                                        <strong>Evaluations: 
                                            <span id="status-text" class="badge <?= $evaluation_status === 'on' ? 'bg-success' : 'bg-danger' ?>" style="font-size: 12px;">
                                                <?= $evaluation_status === 'on' ? 'OPEN' : 'CLOSED' ?>
                                            </span>
                                        </strong>
                                    </label>
                                </div>
                                <small class="form-text text-muted d-block mt-2">
                                    <?= $evaluation_status === 'on' ? 
                                        '✓ Students can submit evaluations' : 
                                        '✗ Evaluations are closed - students will see a notice' 
                                    ?>
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Settings
                            </button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- System Information (Admin Only) -->
                <?php if (getUserRole() === 'admin'): ?>
                <div class="card border-0 shadow-sm" id="system">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-info-circle"></i> System Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>PHP Version:</strong></p>
                                <p class="text-muted"><?= phpversion() ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Server:</strong></p>
                                <p class="text-muted"><?= $_SERVER['SERVER_NAME'] ?? 'Unknown' ?></p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Database:</strong></p>
                                <p class="text-muted">MongoDB</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>System Version:</strong></p>
                                <p class="text-muted">v1.0</p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12">
                                <p><strong>Database Collections:</strong></p>
                                <ul class="list-unstyled">
                                    <li>✓ teachers</li>
                                    <li>✓ questions</li>
                                    <li>✓ evaluations</li>
                                    <li>✓ admins</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>  <!-- Close main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="<?= ASSETS_URL ?>/js/main.js"></script>
    <script src="<?= ASSETS_URL ?>/js/confirmation.js"></script>
    
    <script>
        // Handle active sidebar link as user scrolls or clicks
        const navLinks = document.querySelectorAll('.list-group-item');
        // Get only sections that actually exist in the DOM
        const sections = document.querySelectorAll('.card[id]');
        
        // Remove active class from all links
        function removeActiveClass() {
            navLinks.forEach(link => link.classList.remove('active'));
        }
        
        // Set active link by ID
        function setActiveLink(sectionId) {
            removeActiveClass();
            const activeLink = document.querySelector(`a[href="#${sectionId}"]`);
            if (activeLink) {
                activeLink.classList.add('active');
            }
        }
        
        // Set initial active link on page load
        window.addEventListener('load', function() {
            if (sections.length > 0) {
                setActiveLink(sections[0].getAttribute('id'));
            }
        });
        
        // Set active class when link is clicked
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                const href = this.getAttribute('href');
                const sectionId = href.substring(1); // Remove '#'
                setActiveLink(sectionId);
            });
        });
        
        // Update active class on scroll to highlight current section
        window.addEventListener('scroll', function() {
            let currentSection = '';
            const scrollPosition = window.scrollY + 300; // Offset from top
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const nextSectionTop = section.nextElementSibling ? section.nextElementSibling.offsetTop : document.body.scrollHeight;
                
                if (scrollPosition >= sectionTop && scrollPosition < nextSectionTop) {
                    currentSection = section.getAttribute('id');
                }
            });
            
            if (currentSection) {
                setActiveLink(currentSection);
            }
        });
    </script>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>

