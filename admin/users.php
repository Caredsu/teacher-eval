<?php
/**
 * Users Management - Admin Console
 * Uses new API endpoints instead of direct database queries
 */

require_once '../includes/helpers.php';
require_once '../config/database.php';

initializeSession();
requireLogin();
requireRole('admin');

// Staff cannot access user management
if (isStaff()) {
    setErrorMessage('Access denied. User management is admin-only.');
    redirect(BASE_URL . '/admin/dashboard.php');
}

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
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dark-theme.css?v=2.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/global.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/components.css">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/pages/users.css">
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
                <button class="btn btn-outline-secondary me-2" onclick="printUsersTable()">
                    <i class="bi bi-printer"></i> Print
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
                                <th>Last Updated By</th>
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
                    <input type="hidden" id="ajaxActionInput" name="action" value="add_user">
                    
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
    <script src="<?= ASSETS_URL ?>/js/api-service.js?v=2"></script>
    <script src="<?= ASSETS_URL ?>/js/main.js"></script>
    <script src="<?= ASSETS_URL ?>/js/global.js"></script>
    <script src="<?= ASSETS_URL ?>/js/confirmation.js"></script>
    <script src="<?= ASSETS_URL ?>/js/export-pdf.js"></script>
    <script src="<?= ASSETS_URL ?>/js/pages/users.js"></script>
    
    <!-- Initialization Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a tiny bit to ensure all scripts are loaded
            setTimeout(function() {
                // Initialize the table
                if (typeof initializeUsersPage === 'function') {
                    initializeUsersPage();
                } else {
                    console.error('initializeUsersPage is not defined');
                }
            }, 100);
        });
    </script>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>

