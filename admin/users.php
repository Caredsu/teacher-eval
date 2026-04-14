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
    redirect('/teacher-eval/admin/dashboard.php');
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
    <link rel="stylesheet" href="/teacher-eval/assets/css/dark-theme.css?v=2.0">
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

        .role-admin {
            background: linear-gradient(135deg, #8b5cf6 0%, #06b6d4 100%);
            color: white;
        }

        .role-staff {
            background-color: #06b6d4;
            color: white;
        }

        .modal-header {
            border-bottom: 2px solid #8b5cf6;
        }

        /* Button icon styling */
        .btn-icon {
            width: 36px;
            height: 36px;
            padding: 0 !important;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid #8b5cf6;
            background: transparent !important;
            color: #8b5cf6;
            font-size: 16px;
            transition: all 0.2s ease;
            margin: 0 4px;
        }

        .btn-icon:hover {
            background: #8b5cf6 !important;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
        }

        .btn-icon.deleteBtn {
            border-color: #ef4444;
            color: #ef4444;
        }

        .btn-icon.deleteBtn:hover {
            background: #ef4444 !important;
            color: white;
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .action-buttons {
            display: flex;
            gap: 4px;
            justify-content: center;
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
    <script src="/teacher-eval/assets/js/api-service.js"></script>
    <script src="/teacher-eval/assets/js/export-pdf.js"></script>
    
    <script>
        // Users Management - Uses APIService for all operations
        const userModal = new bootstrap.Modal(document.getElementById('userModal'), {});
        let usersTable = null;
        
        document.addEventListener('DOMContentLoaded', () => {
            initializeUsersTable();
            bindFormEvents();
        });
        
        function initializeUsersTable() {
            usersTable = $('#usersTable').DataTable({
                processing: false,
                serverSide: false,
                columns: [
                    { data: 'username', title: 'Username' },
                    { data: 'email', title: 'Email' },
                    { 
                        data: 'role',
                        title: 'Role',
                        render: function(data, type, row) {
                            const roleClass = data === 'admin' ? 'role-admin' : 'role-staff';
                            const roleLabel = data === 'admin' ? 'Admin' : 'Staff';
                            return `<span class="badge role-badge ${roleClass}">${roleLabel}</span>`;
                        }
                    },
                    {
                        data: 'status',
                        title: 'Status',
                        render: function(data) {
                            const statusClass = data === 'active' ? 'status-active' : 'status-inactive';
                            return `<span class="status-badge ${statusClass}">${data.toUpperCase()}</span>`;
                        }
                    },
                    { 
                        data: 'last_login', 
                        title: 'Last Login',
                        render: function(data) {
                            return data && data !== 'Never' ? data : 'Never';
                        }
                    },
                    { 
                        data: 'created_by', 
                        title: 'Created By',
                        render: function(data) {
                            return data || '-';
                        }
                    },
                    {
                        data: 'id',
                        title: 'Actions',
                        orderable: false,
                        render: function(data, type, row) {
                            return `
                                <div class="action-buttons">
                                    <button class="btn btn-icon editBtn" data-user-id="${data}" data-bs-toggle="tooltip" data-bs-title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <button class="btn btn-icon deleteBtn" data-user-id="${data}" data-bs-toggle="tooltip" data-bs-title="Delete">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                order: [[0, 'asc']],
                pageLength: 10,
                language: {
                    emptyTable: "No users found"
                }
            });
            
            loadUsers();
        }
        
        function loadUsers() {
            api.getUsers()
                .then(response => {
                    console.log('Users response:', response);
                    if (response.success) {
                        // Handle paginated response or raw array
                        let data = response.data;
                        if (data && data.data && Array.isArray(data.data)) {
                            data = data.data;  // Extract from paginated wrapper
                        } else if (!Array.isArray(data)) {
                            data = [];
                        }
                        
                        usersTable.clear().rows.add(data).draw();
                        attachRowEventHandlers();
                    } else {
                        showError(response.message || 'Failed to load users');
                    }
                })
                .catch(error => {
                    console.error('Error loading users:', error);
                    showError('Error loading users: ' + error.message);
                });
        }
        
        function attachRowEventHandlers() {
            $('#usersTable tbody').off('click'); // Remove previous handlers
            
            $('#usersTable tbody').on('click', '.editBtn', function() {
                const userId = $(this).data('user-id');
                editUser(userId);
            });
            
            $('#usersTable tbody').on('click', '.deleteBtn', function() {
                const userId = $(this).data('user-id');
                deleteUser(userId);
            });
            
            initializeTooltips();
        }
        
        function initializeTooltips() {
            // Destroy existing tooltips
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(tooltipTriggerEl => {
                const existingTooltip = bootstrap.Tooltip.getInstance(tooltipTriggerEl);
                if (existingTooltip) {
                    existingTooltip.dispose();
                }
            });
            
            // Initialize new tooltips
            document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(tooltipTriggerEl => {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }
        
        function openUserModal() {
            document.getElementById('userForm').reset();
            document.getElementById('userIdInput').value = '';
            document.getElementById('ajaxActionInput').value = 'add_user';
            document.getElementById('modalTitle').textContent = 'Add New User';
            document.getElementById('passwordField').style.display = 'block';
            document.getElementById('passwordNote').textContent = 'Required for new users';
            document.getElementById('user_password').required = true;
            userModal.show();
        }
        
        function editUser(userId) {
            api.getUser(userId)
                .then(response => {
                    if (response.success && response.data) {
                        const user = response.data;
                        document.getElementById('username').value = user.username;
                        document.getElementById('username').disabled = true; // Cannot change username
                        document.getElementById('user_email').value = user.email || '';
                        document.getElementById('user_role').value = user.role;
                        document.getElementById('user_status').value = user.status;
                        document.getElementById('userIdInput').value = userId;
                        document.getElementById('ajaxActionInput').value = 'update_user';
                        document.getElementById('modalTitle').textContent = 'Edit User';
                        document.getElementById('passwordField').style.display = 'none';
                        document.getElementById('user_password').required = false;
                        userModal.show();
                    } else {
                        showError('Failed to load user details');
                    }
                })
                .catch(error => showError('Error: ' + error.message));
        }
        
        function deleteUser(userId) {
            Swal.fire({
                title: 'Delete User?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) {
                    api.deleteUser(userId)
                        .then(response => {
                            if (response.success) {
                                showSuccess(response.message || 'User deleted successfully');
                                loadUsers();
                            } else {
                                showError(response.message || 'Failed to delete user');
                            }
                        })
                        .catch(error => showError('Error: ' + error.message));
                }
            });
        }
        
        function bindFormEvents() {
            document.getElementById('userForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const isEditing = document.getElementById('userIdInput').value !== '';
                const username = document.getElementById('username').value.trim();
                const email = document.getElementById('user_email').value.trim();
                const password = document.getElementById('user_password').value;
                const role = document.getElementById('user_role').value;
                const status = document.getElementById('user_status').value;
                
                // Clear previous errors
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
                
                if (isEditing) {
                    api.updateUser(document.getElementById('userIdInput').value, {
                        email: email,
                        role: role,
                        status: status
                    })
                        .then(response => {
                            if (response.success) {
                                showSuccess(response.message || 'User updated successfully');
                                userModal.hide();
                                document.getElementById('username').disabled = false;
                                loadUsers();
                            } else {
                                handleValidationErrors(response);
                                showError(response.message || 'Failed to update user');
                            }
                        })
                        .catch(error => showError('Error: ' + error.message));
                } else {
                    api.createUser({
                        username: username,
                        email: email,
                        password: password,
                        role: role,
                        status: status
                    })
                        .then(response => {
                            if (response.success) {
                                showSuccess(response.message || 'User created successfully');
                                userModal.hide();
                                loadUsers();
                            } else {
                                handleValidationErrors(response);
                                showError(response.message || 'Failed to create user');
                            }
                        })
                        .catch(error => showError('Error: ' + error.message));
                }
            });
        }
        
        function handleValidationErrors(response) {
            if (response.data && response.data.errors) {
                const errors = response.data.errors;
                
                // Map field names to input IDs
                const fieldMap = {
                    'username': 'username',
                    'email': 'user_email',
                    'password': 'user_password',
                    'role': 'user_role',
                    'status': 'user_status'
                };
                
                Object.entries(errors).forEach(([field, messages]) => {
                    const fieldId = fieldMap[field];
                    if (fieldId) {
                        const element = document.getElementById(fieldId);
                        if (element) {
                            element.classList.add('is-invalid');
                            const feedback = document.createElement('div');
                            feedback.className = 'invalid-feedback d-block';
                            feedback.textContent = Array.isArray(messages) ? messages[0] : messages;
                            element.parentNode.appendChild(feedback);
                        }
                    }
                });
            }
        }
        
        function exportUsersPDF() {
            const rows = usersTable.rows({ search: 'applied' }).data().toArray();
            const doc = new jsPDF();
            
            doc.setFontSize(16);
            doc.text('Users Report', 14, 22);
            
            doc.setFontSize(11);
            doc.text('Generated: ' + new Date().toLocaleString(), 14, 32);
            
            const columns = ['Username', 'Email', 'Role', 'Status', 'Created By'];
            const data = rows.map(row => [
                row.username,
                row.email,
                row.role_display,
                row.status,
                row.created_by
            ]);
            
            doc.autoTable({
                startY: 40,
                head: [columns],
                body: data,
                theme: 'grid',
                styles: { fontSize: 10 }
            });
            
            doc.save('users_' + new Date().getTime() + '.pdf');
        }
        
        function showSuccess(message) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: message,
                timer: 2000,
                showConfirmButton: false
            });
        }
        
        function showError(message) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message
            });
        }
    </script>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>

