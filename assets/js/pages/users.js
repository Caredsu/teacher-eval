/**
 * Users Page JavaScript
 * Specific functionality for admin/users.php
 */

// Users DataTable
let usersTable = null;

// Initialize Users Page
function initializeUsersPage() {
    initializeUsersTable();
    setupUserFilters();
    loadUsersData();
}

// Initialize DataTable for Users
function initializeUsersTable() {
    const usersTableElement = document.getElementById('usersTable');
    if (!usersTableElement || typeof $ === 'undefined') return;
    
    // Don't reinitialize if already initialized
    if (usersTable && $.fn.DataTable.isDataTable('#usersTable')) {
        return;
    }
    
    usersTable = $('#usersTable').DataTable({
        responsive: true,
        paging: true,
        pageLength: 10,
        ordering: true,
        searching: true,
        language: {
            search: 'Filter users:',
            lengthMenu: 'Show _MENU_ users per page',
            info: 'Showing _START_ to _END_ of _TOTAL_ users'
        },
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>t<"row"<"col-md-6"i><"col-md-6"p>>'
    });
}

// Setup User Filters
function setupUserFilters() {
    const filterButton = document.getElementById('filterButton');
    if (filterButton) {
        filterButton.addEventListener('click', applyUserFilters);
    }
}

// Apply User Filters
function applyUserFilters() {
    const role = document.getElementById('roleFilter')?.value;
    const status = document.getElementById('statusFilter')?.value;
    const search = document.getElementById('searchInput')?.value;
    
    const filters = {
        role: role || '',
        status: status || '',
        search: search || ''
    };
    
    loadUsersData(filters);
}

// Load Users Data
function loadUsersData(filters = {}) {
    // Call the API endpoint instead
    fetch('/teacher-eval/api/users.php')
        .then(response => response.json())
        .then(data => {
            console.log('Users API Response:', data);
            if (data.success && usersTable && data.data && Array.isArray(data.data.data)) {
                usersTable.clear();
                data.data.data.forEach(user => {
                    const statusClass = user.status === 'active' ? 'user-status-active' : 'user-status-inactive';
                    const statusBadge = '<span class="' + statusClass + '">' + (user.status || 'active') + '</span>';
                    const lastLogin = user.last_login || 'Never';
                    const createdBy = user.created_by || 'System';
                    const updatedBy = user.updated_by || 'N/A';
                    
                    usersTable.row.add([
                        user.username || user.name || '',
                        user.email || '',
                        user.role || 'N/A',
                        statusBadge,
                        lastLogin,
                        createdBy,
                        updatedBy,
                        '<button class="btn btn-sm btn-outline-primary" onclick="editUser(' + user.id + ')">Edit</button> ' +
                        '<button class="btn btn-sm btn-outline-danger" onclick="deleteUser(' + user.id + ')">Delete</button>'
                    ]);
                });
                usersTable.draw();
            } else {
                console.warn('No users data received:', data);
            }
        })
        .catch(error => {
            console.error('Error loading users:', error);
        });
}

// Edit User
function editUser(userId) {
    fetch(`/teacher-eval/api/users.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showEditUserModal(data.user);
            }
        });
}

// Show Edit User Modal
function showEditUserModal(user) {
    const modal = document.getElementById('editUserModal');
    if (!modal) return;
    
    // Populate form fields
    document.getElementById('userId').value = user.id;
    document.getElementById('userName').value = user.name || '';
    document.getElementById('userEmail').value = user.email || '';
    document.getElementById('userRole').value = user.role || '';
    document.getElementById('userStatus').value = user.status || 'active';
    
    // Show modal
    if (typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

// Delete User
function deleteUser(userId) {
    showConfirm('Delete User', 'Are you sure you want to delete this user?', () => {
        fetch(`/teacher-eval/api/users.php?id=${userId}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess('User Deleted', 'User has been deleted successfully');
                if (usersTable) {
                    usersTable.ajax.reload();
                }
            } else {
                showError('Error', data.message || 'Failed to delete user');
            }
        });
    });
}

// Save User Changes
function saveUserChanges() {
    const form = document.getElementById('editUserForm');
    const formData = new FormData(form);
    const userId = document.getElementById('userId').value;
    
    fetch(`/teacher-eval/api/users.php?id=${userId}`, {
        method: 'PUT',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('User Updated', 'User has been updated successfully');
            bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
            if (usersTable) {
                usersTable.ajax.reload();
            }
        } else {
            showError('Error', data.message || 'Failed to update user');
        }
    });
}


