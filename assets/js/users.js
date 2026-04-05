/**
 * Users Management - Admin Users CRUD
 */

let usersTable;

// Function to get CSRF token
function getCSRFToken() {
    return document.querySelector('input[name="csrf_token"]')?.value || '';
}

// Initialize DataTable on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeUsersTable();
    setupFormHandlers();
});

/**
 * Initialize Users DataTable
 */
function initializeUsersTable() {
    usersTable = $('#usersTable').DataTable({
        processing: true,
        ajax: {
            url: '/teacher-eval/admin/users.php',
            type: 'POST',
            data: function(d) {
                d = {
                    ajax_action: 'get_users',
                    csrf_token: getCSRFToken()
                };
                return d;
            },
            dataSrc: function(json) {
                return json.data || [];
            }
        },
        columns: [
            { data: 'username', name: 'username', render: function(data) {
                return $('<div>').text(data).html();
            }},
            { data: 'email', name: 'email', render: function(data) {
                return $('<div>').text(data).html();
            }},
            { data: 'role_display', name: 'role', width: '12%' },
            { data: 'status', name: 'status', width: '10%', render: function(data) {
                const badge = data === 'active' 
                    ? '<span class="status-badge status-active">✓ Active</span>'
                    : '<span class="status-badge status-inactive">✗ Inactive</span>';
                return badge;
            }},
            { data: 'created_at', name: 'created_at', width: '15%' },
            { data: 'last_login', name: 'last_login', width: '15%' },
            { data: '_id', name: 'actions', width: '15%', orderable: false, searchable: false, render: function(data) {
                return `
                    <button class="btn btn-sm btn-outline-primary btn-edit-user" data-id="${data}" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger btn-delete-user" data-id="${data}" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>
                `;
            }}
        ],
        order: [[0, 'asc']],
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
        responsive: true,
        autoWidth: false,
        language: {
            emptyTable: 'No users found. Click "Add New User" to create one.',
            loadingRecords: 'Loading users...',
            processing: 'Processing...',
            search: '_INPUT_',
            searchPlaceholder: 'Search users...',
            info: 'Showing _START_ to _END_ of _TOTAL_ users',
            infoEmpty: 'No users to display',
            paginate: {
                first: 'First',
                last: 'Last',
                next: 'Next',
                previous: 'Previous'
            }
        }
    });

    // Setup action buttons
    usersTable.on('draw', function() {
        setupActionButtons();
    });
}

/**
 * Setup action button listeners
 */
function setupActionButtons() {
    // Edit buttons
    document.querySelectorAll('.btn-edit-user').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.getAttribute('data-id');
            editUser(userId);
        });
    });

    // Delete buttons
    document.querySelectorAll('.btn-delete-user').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const userId = this.getAttribute('data-id');
            deleteUser(userId);
        });
    });
}

/**
 * Open modal for adding new user
 */
function openUserModal() {
    document.getElementById('userForm').reset();
    document.getElementById('userIdInput').value = '';
    document.getElementById('ajaxActionInput').value = 'add_user';
    document.getElementById('modalTitle').textContent = 'Add New User';
    document.getElementById('passwordField').style.display = 'block';
    document.getElementById('user_password').required = true;
    
    Modal.show('userModal');
}

/**
 * Edit existing user
 */
function editUser(userId) {
    const formData = new FormData();
    formData.append('ajax_action', 'get_user');
    formData.append('user_id', userId);
    formData.append('csrf_token', getCSRFToken());

    fetch('/teacher-eval/admin/users.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success && result.data) {
            const user = result.data;
            document.getElementById('userIdInput').value = user._id || '';
            document.getElementById('username').value = user.username || '';
            document.getElementById('user_email').value = user.email || '';
            document.getElementById('user_role').value = user.role || 'admin';
            document.getElementById('user_status').value = user.status || 'active';
            document.getElementById('ajaxActionInput').value = 'update_user';
            document.getElementById('modalTitle').textContent = 'Edit User';
            // Hide password field for edit
            document.getElementById('passwordField').style.display = 'none';
            document.getElementById('user_password').required = false;
            
            Modal.show('userModal');
        } else {
            Toast.error(result.message || 'Failed to load user');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Toast.error('An error occurred');
    });
}

/**
 * Delete user
 */
function deleteUser(userId) {
    Swal.fire({
        title: 'Delete User?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#667eea',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ajax_action', 'delete_user');
            formData.append('user_id', userId);
            formData.append('csrf_token', getCSRFToken());

            fetch('/teacher-eval/admin/users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    Toast.success('User deleted successfully');
                    usersTable.ajax.reload();
                } else {
                    Toast.error(result.message || 'Failed to delete user');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.error('An error occurred');
            });
        }
    });
}

/**
 * Setup form handlers
 */
function setupFormHandlers() {
    const userForm = document.getElementById('userForm');
    
    if (userForm) {
        userForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('/teacher-eval/admin/users.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    Toast.success(result.message || 'User saved successfully');
                    Modal.hide('userModal');
                    userForm.reset();
                    usersTable.ajax.reload();
                } else {
                    Toast.error(result.message || 'Failed to save user');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.error('An error occurred');
            });
        });
    }
}
