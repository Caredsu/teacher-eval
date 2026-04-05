<?php
/**
 * Users Management - Role-Based Access Control
 * Admin Only
 */

// Strict output handling
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once '../includes/helpers.php';
require_once '../config/database.php';

initializeSession();
requireLogin();

// Staff cannot access user management
if (isStaff()) {
    setErrorMessage('Access denied. User management is admin-only.');
    redirect('/teacher-eval/admin/dashboard.php');
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    // Clear ALL output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Start fresh buffer for JSON only
    ob_start();
    
    try {
        // Check authorization for AJAX
        if (!hasRole('admin')) {
            http_response_code(403);
            error_log('AJAX Auth Failed: User role = ' . (getUserRole() ?? 'NULL'));
            throw new Exception('Access Denied: Insufficient permissions.');
        }
        
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            http_response_code(403);
            error_log('AJAX CSRF Failed');
            throw new Exception('Security token invalid.');
        }
        
        $ajax_action = sanitizeInput($_POST['ajax_action']);
        
        // Get all users
        if ($ajax_action === 'get_users') {
            $users = $admins_collection->find([], ['sort' => ['username' => 1]])->toArray();
            $formatted = [];
            
            foreach ($users as $user) {
                $role = $user['role'] ?? 'admin';
                $formatted[] = [
                    '_id' => objectIdToString($user['_id']),
                    'username' => $user['username'] ?? '',
                    'email' => $user['email'] ?? '',
                    'role' => $role,
                    'role_display' => getRoleDisplayName($role),
                    'status' => $user['status'] ?? 'active',
                    'created_at' => formatDateTime($user['created_at'] ?? ''),
                    'created_by' => $user['created_by'] ?? 'System',
                    'updated_at' => formatDateTime($user['updated_at'] ?? ''),
                    'updated_by' => $user['updated_by'] ?? 'System',
                    'last_login' => $user['last_login'] ? formatDateTime($user['last_login']) : 'Never'
                ];
            }
            
            // Clear any accidental output before JSON
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'message' => '',
                'data' => $formatted
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit;
        }
        
        // Get single user
        if ($ajax_action === 'get_user') {
            $user_id = sanitizeInput($_POST['user_id'] ?? '');
            
            if (!isValidObjectId($user_id)) {
                http_response_code(400);
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Invalid user ID', 'data' => []]);
                exit;
            }
            
            $user = $admins_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
            
            if (!$user) {
                http_response_code(404);
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'User not found', 'data' => []]);
                exit;
            }
            
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'message' => '',
                'data' => [
                    '_id' => objectIdToString($user['_id']),
                    'username' => $user['username'] ?? '',
                    'email' => $user['email'] ?? '',
                    'role' => $user['role'] ?? 'admin',
                    'status' => $user['status'] ?? 'active'
                ]
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Add user
        if ($ajax_action === 'add_user') {
            $username = sanitizeInput($_POST['username'] ?? '');
            $email = sanitizeInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = sanitizeInput($_POST['role'] ?? 'admin');
            $status = sanitizeInput($_POST['status'] ?? 'active');
            
            // Validation
            if (empty($username) || strlen($username) < 3) {
                http_response_code(400);
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Username must be at least 3 characters', 'data' => []]);
                exit;
            }
            
            if (empty($password) || strlen($password) < 6) {
                http_response_code(400);
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters', 'data' => []]);
                exit;
            }
            
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Invalid email format', 'data' => []]);
                exit;
            }
            
            // Check if username exists
            $existing = $admins_collection->findOne(['username' => $username]);
            if ($existing) {
                http_response_code(409);
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Username already exists', 'data' => []]);
                exit;
            }
            
            if (!in_array($role, array_keys(USER_ROLES))) {
                $role = 'admin';
            }
            
            if (!in_array($status, ['active', 'inactive'])) {
                $status = 'active';
            }
            
            // Insert user
            $admins_collection->insertOne([
                'username' => $username,
                'email' => $email,
                'password' => hashPassword($password),
                'role' => $role,
                'status' => $status,
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'created_by' => getLoggedInAdminUsername(),
                'updated_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_by' => getLoggedInAdminUsername(),
                'last_login' => null
            ]);
            
            logActivity('USER_CREATED', 'Created user: ' . $username . ' with role: ' . $role);
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'User created successfully!', 'data' => []]);
            exit;
        }
        
        // Update user
        if ($ajax_action === 'update_user') {
            $user_id = sanitizeInput($_POST['user_id'] ?? '');
            $email = sanitizeInput($_POST['email'] ?? '');
            $role = sanitizeInput($_POST['role'] ?? 'admin');
            $status = sanitizeInput($_POST['status'] ?? 'active');
            
            if (!isValidObjectId($user_id)) {
                http_response_code(400);
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Invalid user ID', 'data' => []]);
                exit;
            }
            
            if (!in_array($role, array_keys(USER_ROLES))) {
                $role = 'admin';
            }
            
            if (!in_array($status, ['active', 'inactive'])) {
                $status = 'active';
            }
            
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Invalid email format', 'data' => []]);
                exit;
            }
            
            // Prevent self-demotion from admin
            $user = $admins_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
            $current_role = $user['role'] ?? 'admin';
            
            if ($current_role === 'admin' && $role !== 'admin' && objectIdToString($user['_id']) === getLoggedInAdmin()) {
                http_response_code(403);
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'You cannot remove your own admin role', 'data' => []]);
                exit;
            }
            
            $admins_collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($user_id)],
                [
                    '$set' => [
                        'email' => $email,
                        'role' => $role,
                        'status' => $status,
                        'updated_at' => new MongoDB\BSON\UTCDateTime(),
                        'updated_by' => getLoggedInAdminUsername()
                    ]
                ]
            );
            
            logActivity('USER_UPDATED', 'Updated user ID: ' . $user_id . ', new role: ' . $role);
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'User updated successfully!', 'data' => []]);
            exit;
        }
        
        // Delete user
        if ($ajax_action === 'delete_user') {
            $user_id = sanitizeInput($_POST['user_id'] ?? '');
            
            if (!isValidObjectId($user_id)) {
                http_response_code(400);
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Invalid user ID', 'data' => []]);
                exit;
            }
            
            // Prevent self-deletion
            if ($user_id === getLoggedInAdmin()) {
                http_response_code(403);
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'You cannot delete your own account', 'data' => []]);
                exit;
            }
            
            $user = $admins_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
            
            if (!$user) {
                http_response_code(404);
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'User not found', 'data' => []]);
                exit;
            }
            
            $admins_collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
            
            logActivity('USER_DELETED', 'Deleted user: ' . ($user['username'] ?? 'Unknown'));
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'User deleted successfully!', 'data' => []]);
            exit;
        }
        
        // Reset password
        if ($ajax_action === 'reset_password') {
            $user_id = sanitizeInput($_POST['user_id'] ?? '');
            $new_password = $_POST['new_password'] ?? '';
            
            if (!isValidObjectId($user_id)) {
                http_response_code(400);
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Invalid user ID', 'data' => []]);
                exit;
            }
            
            if (empty($new_password) || strlen($new_password) < 6) {
                http_response_code(400);
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters', 'data' => []]);
                exit;
            }
            
            $user = $admins_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($user_id)]);
            
            if (!$user) {
                http_response_code(404);
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'User not found', 'data' => []]);
                exit;
            }
            
            $admins_collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($user_id)],
                [
                    '$set' => [
                        'password' => hashPassword($new_password),
                        'updated_at' => new MongoDB\BSON\UTCDateTime(),
                        'updated_by' => getLoggedInAdminUsername()
                    ]
                ]
            );
            
            logActivity('PASSWORD_RESET', 'Admin reset password for user ID: ' . $user_id);
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Password reset successfully!', 'data' => []]);
            exit;
        }
        
    } catch (\Exception $e) {
        http_response_code(500);
        error_log('AJAX Error: ' . $e->getMessage());
        ob_end_clean();
        echo json_encode([
            'success' => false,
            'message' => 'Server error: ' . $e->getMessage(),
            'data' => []
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    exit;
}

// For non-AJAX requests, require admin role and output HTML
requireRole('admin');

$success_msg = getSuccessMessage();
$error_msg = getErrorMessage();

// Flush and close output buffer before HTML
while (ob_get_level()) {
    ob_end_clean();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management - Teacher Evaluation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/teacher-eval/assets/css/dark-theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .role-badge {
            font-size: 11px;
            padding: 4px 8px;
            font-weight: 600;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            display: inline-block;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        body.dark-mode .status-active {
            background: #1b5e20;
            color: #51cf66;
        }

        body.dark-mode .status-inactive {
            background: #5a1818;
            color: #ff6b6b;
        }

        .role-admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .role-staff {
            background-color: #0d6efd;
            color: white;
        }

        .modal-header {
            border-bottom: 2px solid #667eea;
        }

        body.dark-mode .modal-header {
            background: #2d2d2d;
        }

        body.dark-mode .modal-body {
            background: #2d2d2d;
        }

        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background-color: #1a1a1a;
            color: #e0e0e0;
            border-color: #444;
        }

        body.dark-mode .form-control:focus,
        body.dark-mode .form-select:focus {
            border-color: #667eea;
        }

        /* Disable animations on table hover */
        .table tbody tr,
        .table tbody tr * {
            animation: none !important;
            -webkit-animation: none !important;
            -moz-animation: none !important;
            -o-animation: none !important;
            transition: none !important;
            -webkit-transition: none !important;
            -moz-transition: none !important;
            -o-transition: none !important;
            transform: none !important;
            -webkit-transform: none !important;
            -moz-transform: none !important;
            -o-transform: none !important;
            animation-play-state: paused !important;
            -webkit-animation-play-state: paused !important;
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
            <div class="col-md-6">
                <h1 class="h2"><i class="bi bi-people"></i> Users Management</h1>
                <p class="text-muted">Manage admin users and their roles</p>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-outline-secondary me-2" onclick="exportUsersPDF()">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </button>
                <button class="btn btn-primary btn-lg" onclick="openUserModal()">
                    <i class="bi bi-plus-circle"></i> Add New User
                </button>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= escapeOutput($success_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_msg)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <?= escapeOutput($error_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <!-- Users Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <h5 class="mb-0">All Users</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="usersTable" class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Created By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="userForm">
                    <?php outputCSRFToken(); ?>
                    <input type="hidden" id="userIdInput" name="user_id" value="">
                    <input type="hidden" id="ajaxActionInput" name="ajax_action" value="add_user">
                    
                    <div class="modal-body">
                        <!-- Username -->
                        <div class="mb-3">
                            <label for="username" class="form-label">Username *</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="username" 
                                name="username"
                                placeholder="e.g., john.admin"
                                minlength="3"
                                required
                            >
                            <small class="form-text text-muted" id="usernameNote">New field - required for creation</small>
                        </div>
                        
                        <!-- Email -->
                        <div class="mb-3">
                            <label for="user_email" class="form-label">Email</label>
                            <input 
                                type="email" 
                                class="form-control" 
                                id="user_email" 
                                name="email"
                                placeholder="e.g., john@school.edu"
                            >
                        </div>
                        
                        <!-- Password -->
                        <div class="mb-3" id="passwordField">
                            <label for="user_password" class="form-label">Password *</label>
                            <input 
                                type="password" 
                                class="form-control" 
                                id="user_password" 
                                name="password"
                                placeholder="Minimum 6 characters"
                                minlength="6"
                                required
                            >
                            <small class="form-text text-muted" id="passwordNote">New field - required for creation</small>
                        </div>
                        
                        <!-- Role -->
                        <div class="mb-3">
                            <label for="user_role" class="form-label">Role *</label>
                            <select class="form-select" id="user_role" name="role" required>
                                <?php foreach (USER_ROLES as $role_key => $role_name): ?>
                                    <option value="<?= escapeOutput($role_key) ?>">
                                        <?= escapeOutput($role_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">
                                Role determines what features the user can access
                            </small>
                        </div>
                        
                        <!-- Status -->
                        <div class="mb-3">
                            <label for="user_status" class="form-label">Status</label>
                            <select class="form-select" id="user_status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>  <!-- Close main-content -->

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="/teacher-eval/assets/js/main.js"></script>
    <script src="/teacher-eval/assets/js/users.js"></script>
    <script src="/teacher-eval/assets/js/export-pdf.js"></script>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>

