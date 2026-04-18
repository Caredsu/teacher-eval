/**
 * Teachers Management - Edit/Delete/Add Teacher
 */

let teachersTable;
let teacherIdBuffer = null;

// Function to get CSRF token
function getCSRFToken() {
    return document.querySelector('input[name="csrf_token"]')?.value || '';
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeTeachersTable();
    setupFormHandlers();
});

/**
 * Initialize Teachers DataTable
 */
function initializeTeachersTable() {
    teachersTable = $('#teachersTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '/teacher-eval/admin/teachers.php',
            type: 'POST',
            data: function(d) {
                d.ajax_action = 'get_teachers';
                d.csrf_token = getCSRFToken();
                return d;
            }
        },
        columns: [
            { data: 'name', name: 'name' },
            { data: 'department', name: 'department', orderable: false, searchable: false, render: function(data) {
                return data;
            }},
            { data: 'email', name: 'email' },
            { data: 'status_badge', name: 'status', orderable: false, searchable: false, render: function(data) {
                return data;
            }},
            { data: 'created_at', name: 'created_at' },
            { data: 'updated_at', name: 'updated_at' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, render: function(data) {
                return data;
            }}
        ],
        order: [[4, 'desc']],
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
        responsive: true,
        autoWidth: false,
        language: {
            emptyTable: 'No teachers found. Click "Add New Teacher" to create one.',
            loadingRecords: 'Loading teachers...',
            processing: 'Processing...',
            search: '_INPUT_',
            searchPlaceholder: 'Search name or email...',
            info: 'Showing _START_ to _END_ of _TOTAL_ teachers',
            infoEmpty: 'No Teachers to display',
            paginate: {
                first: 'First',
                last: 'Last',
                next: 'Next',
                previous: 'Previous'
            }
        }
    });

    // Reinitialize action buttons on each draw
    teachersTable.on('draw', function() {
        setupDeleteButtons();
        setupEditButtons();
    });
}

/**
 * Setup delete buttons
 */
function setupDeleteButtons() {
    document.querySelectorAll('.btn-delete-teacher').forEach(btn => {
        btn.removeEventListener('click', handleDeleteClick);
        btn.addEventListener('click', handleDeleteClick);
    });
}

function handleDeleteClick(e) {
    e.preventDefault();
    const teacherId = this.getAttribute('data-id');
    const name = this.getAttribute('data-name');
    
    Swal.fire({
        title: 'Delete Teacher?',
        text: `This will remove ${name} from the system. This action cannot be undone.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#667eea',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '/teacher-eval/admin/teachers.php?delete=' + teacherId + '&csrf=' + getCSRFToken();
        }
    });
}

/**
 * Setup edit buttons
 */
function setupEditButtons() {
    document.querySelectorAll('.btn-edit-teacher').forEach(btn => {
        btn.removeEventListener('click', handleEditClick);
        btn.addEventListener('click', handleEditClick);
    });
}

function handleEditClick(e) {
    e.preventDefault();
    const teacherId = this.getAttribute('data-id');
    editTeacher(teacherId);
}

/**
 * Open modal for adding new teacher
 */
function openTeacherModal() {
    document.getElementById('teacherForm').reset();
    document.getElementById('teacherIdInput').value = '';
    document.getElementById('actionTypeInput').value = 'add';
    document.getElementById('modalTitleText').textContent = 'Add New Teacher';
    
    Modal.show('teacherModal');
}

/**
 * Edit existing teacher
 */
function editTeacher(teacherId) {
    const formData = new FormData();
    formData.append('get_teacher_data', teacherId);

    fetch('/teacher-eval/admin/teachers.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success && result.teacher) {
            const teacher = result.teacher;
            document.getElementById('teacherIdInput').value = teacher._id || '';
            document.getElementById('first_name').value = teacher.first_name || '';
            document.getElementById('middle_name').value = teacher.middle_name || '';
            document.getElementById('last_name').value = teacher.last_name || '';
            document.getElementById('department').value = teacher.department || '';
            document.getElementById('email').value = teacher.email || '';
            document.getElementById('status').value = teacher.status || 'active';
            document.getElementById('actionTypeInput').value = 'edit';
            document.getElementById('modalTitleText').textContent = 'Edit Teacher';
            
            Modal.show('teacherModal');
        } else {
            Toast.error(result.message || 'Failed to load teacher');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Toast.error('An error occurred while loading the teacher');
    });
}

/**
 * Setup form handlers
 */
function setupFormHandlers() {
    const teacherForm = document.getElementById('teacherForm');
    
    if (teacherForm) {
        teacherForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            // Add CSRF token
            formData.append('csrf_token', getCSRFToken());
            
            fetch('/teacher-eval/admin/teachers.php', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    Toast.success(result.message || 'Teacher saved successfully');
                    Modal.hide('teacherModal');
                    teacherForm.reset();
                    // Reload page to show updated data
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    Toast.error(result.message || 'Failed to save teacher');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.error('An error occurred while saving the teacher');
            });
        });
    }
}

/**
 * Reload teachers table
 */
function reloadTeachersTable() {
    location.reload();
}
