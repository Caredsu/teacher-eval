<?php
/**
 * Teachers Management - Admin Console
 * Uses new API endpoints instead of direct database queries
 */

require_once '../includes/helpers.php';
require_once '../config/database.php';

initializeSession();
requireLogin();
requireRole('admin');

// Constants for validation
const ALLOWED_DEPARTMENTS = ['ECT', 'EDUC', 'CCJE', 'BHT'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teachers Management - Teacher Evaluation System</title>
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

        .dept-badge {
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
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

        .dept-ect { background: #cfe2ff; color: #084298; }
        .dept-educ { background: #d1e7dd; color: #0f5132; }
        .dept-ccje { background: #fff3cd; color: #664d03; }
        .dept-bht { background: #f8d7da; color: #721c24; }

        body.dark-mode .status-active {
            background: #1b5e20;
            color: #51cf66;
        }

        body.dark-mode .status-inactive {
            background: #5a1818;
            color: #ff6b6b;
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
                    <h1 class="h2"><i class="bi bi-people"></i> Teachers Management</h1>
                    <p class="text-muted">Manage teacher information and assignments</p>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-outline-secondary me-2" onclick="exportTeachersPDF()">
                        <i class="bi bi-file-pdf"></i> Export PDF
                    </button>
                    <button class="btn btn-primary btn-lg" onclick="openTeacherModal()">
                        <i class="bi bi-plus-circle"></i> Add New Teacher
                    </button>
                </div>
            </div>

            <!-- Teachers Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">All Teachers</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="teachersTable" class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Full Name</th>
                                    <th>Department</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Teacher Modal -->
    <div class="modal fade" id="teacherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="teacherForm">
                    <div class="modal-body">
                        <!-- First Name -->
                        <div class="mb-3">
                            <label for="first_name" class="form-label">First Name *</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="first_name" 
                                name="first_name"
                                placeholder="e.g., John"
                                minlength="2"
                                required
                            >
                        </div>

                        <!-- Middle Name -->
                        <div class="mb-3">
                            <label for="middle_name" class="form-label">Middle Name</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="middle_name" 
                                name="middle_name"
                                placeholder="e.g., Carlos"
                            >
                        </div>

                        <!-- Last Name -->
                        <div class="mb-3">
                            <label for="last_name" class="form-label">Last Name *</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="last_name" 
                                name="last_name"
                                placeholder="e.g., Dela Cruz"
                                minlength="2"
                                required
                            >
                        </div>

                        <!-- Department -->
                        <div class="mb-3">
                            <label for="department" class="form-label">Department *</label>
                            <select class="form-select" id="department" name="department" required>
                                <option value="">-- Select Department --</option>
                                <?php foreach (ALLOWED_DEPARTMENTS as $dept): ?>
                                    <option value="<?= escapeOutput($dept) ?>">
                                        <?= escapeOutput($dept) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input 
                                type="email" 
                                class="form-control" 
                                id="email" 
                                name="email"
                                placeholder="e.g., john@school.edu"
                            >
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Teacher
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
        // Teachers Management - Uses APIService for all operations
        const teacherModal = new bootstrap.Modal(document.getElementById('teacherModal'), {});
        let teachersTable = null;
        
        document.addEventListener('DOMContentLoaded', () => {
            initializeTeachersTable();
            bindFormEvents();
        });
        
        function initializeTeachersTable() {
            teachersTable = $('#teachersTable').DataTable({
                processing: true,
                serverSide: false,
                columns: [
                    { data: 'full_name', title: 'Full Name' },
                    { 
                        data: 'department',
                        title: 'Department',
                        render: function(data) {
                            return `<span class="dept-badge dept-${data.toLowerCase()}">${data}</span>`;
                        }
                    },
                    { data: 'email', title: 'Email' },
                    {
                        data: 'status',
                        title: 'Status',
                        render: function(data) {
                            const statusClass = data === 'active' ? 'status-active' : 'status-inactive';
                            return `<span class="status-badge ${statusClass}">${data.toUpperCase()}</span>`;
                        }
                    },
                    { data: 'created_at', title: 'Created' },
                    { data: 'updated_at', title: 'Last Updated' },
                    {
                        data: 'id',
                        title: 'Actions',
                        orderable: false,
                        render: function(data, type, row) {
                            return `
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-outline-primary editBtn" data-teacher-id="${data}">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger deleteBtn" data-teacher-id="${data}">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </div>
                            `;
                        }
                    }
                ],
                order: [[0, 'asc']],
                pageLength: 10,
                language: {
                    emptyTable: "No teachers found"
                }
            });
            
            loadTeachers();
        }
        
        function loadTeachers() {
            api.getTeachers()
                .then(response => {
                    console.log('Teachers response:', response);
                    if (response.success) {
                        // Handle paginated response or raw array
                        let data = response.data;
                        if (data && data.data && Array.isArray(data.data)) {
                            data = data.data;  // Extract from paginated wrapper
                        } else if (!Array.isArray(data)) {
                            data = [];
                        }
                        
                        // Format data for DataTable
                        const formattedData = data.map(teacher => ({
                            ...teacher,
                            full_name: `${teacher.first_name} ${teacher.middle_name} ${teacher.last_name}`.trim()
                        }));
                        teachersTable.clear().rows.add(formattedData).draw();
                        attachRowEventHandlers();
                    } else {
                        showError('Failed to load teachers');
                    }
                })
                .catch(error => {
                    console.error('Error loading teachers:', error);
                    showError('Error loading teachers: ' + error.message);
                });
        }
        
        function attachRowEventHandlers() {
            $('#teachersTable tbody').off('click'); // Remove previous handlers
            
            $('#teachersTable tbody').on('click', '.editBtn', function() {
                const teacherId = $(this).data('teacher-id');
                editTeacher(teacherId);
            });
            
            $('#teachersTable tbody').on('click', '.deleteBtn', function() {
                const teacherId = $(this).data('teacher-id');
                deleteTeacher(teacherId);
            });
        }
        
        function openTeacherModal() {
            document.getElementById('teacherForm').reset();
            document.getElementById('modalTitle').textContent = 'Add New Teacher';
            teacherModal.show();
        }
        
        function editTeacher(teacherId) {
            api.getTeacher(teacherId)
                .then(response => {
                    if (response.success && response.data) {
                        const teacher = response.data;
                        document.getElementById('first_name').value = teacher.first_name || '';
                        document.getElementById('middle_name').value = teacher.middle_name || '';
                        document.getElementById('last_name').value = teacher.last_name || '';
                        document.getElementById('department').value = teacher.department || '';
                        document.getElementById('email').value = teacher.email || '';
                        document.getElementById('status').value = teacher.status || 'active';
                        document.getElementById('teacherForm').dataset.teacherId = teacherId;
                        document.getElementById('modalTitle').textContent = 'Edit Teacher';
                        teacherModal.show();
                    } else {
                        showError('Failed to load teacher details');
                    }
                })
                .catch(error => showError('Error: ' + error.message));
        }
        
        function deleteTeacher(teacherId) {
            Swal.fire({
                title: 'Delete Teacher?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) {
                    api.deleteTeacher(teacherId)
                        .then(response => {
                            if (response.success) {
                                showSuccess(response.message || 'Teacher deleted successfully');
                                loadTeachers();
                            } else {
                                showError(response.message || 'Failed to delete teacher');
                            }
                        })
                        .catch(error => showError('Error: ' + error.message));
                }
            });
        }
        
        function bindFormEvents() {
            document.getElementById('teacherForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const teacherId = this.dataset.teacherId;
                const isEditing = !!teacherId;
                const firstName = document.getElementById('first_name').value.trim();
                const middleName = document.getElementById('middle_name').value.trim();
                const lastName = document.getElementById('last_name').value.trim();
                const department = document.getElementById('department').value;
                const email = document.getElementById('email').value.trim();
                const status = document.getElementById('status').value;
                
                // Clear previous errors
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
                
                if (isEditing) {
                    api.updateTeacher(teacherId, {
                        first_name: firstName,
                        middle_name: middleName,
                        last_name: lastName,
                        department: department,
                        email: email,
                        status: status
                    })
                        .then(response => {
                            if (response.success) {
                                showSuccess(response.message || 'Teacher updated successfully');
                                teacherModal.hide();
                                delete this.dataset.teacherId;
                                loadTeachers();
                            } else {
                                handleValidationErrors(response);
                                showError(response.message || 'Failed to update teacher');
                            }
                        })
                        .catch(error => showError('Error: ' + error.message));
                } else {
                    api.createTeacher({
                        first_name: firstName,
                        middle_name: middleName,
                        last_name: lastName,
                        department: department,
                        email: email,
                        status: status
                    })
                        .then(response => {
                            if (response.success) {
                                showSuccess(response.message || 'Teacher created successfully');
                                teacherModal.hide();
                                loadTeachers();
                            } else {
                                handleValidationErrors(response);
                                showError(response.message || 'Failed to create teacher');
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
                    'first_name': 'first_name',
                    'middle_name': 'middle_name',
                    'last_name': 'last_name',
                    'department': 'department',
                    'email': 'email',
                    'status': 'status'
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
        
        function exportTeachersPDF() {
            const rows = teachersTable.rows({ search: 'applied' }).data().toArray();
            const doc = new jsPDF();
            
            doc.setFontSize(16);
            doc.text('Teachers Report', 14, 22);
            
            doc.setFontSize(11);
            doc.text('Generated: ' + new Date().toLocaleString(), 14, 32);
            
            const columns = ['Full Name', 'Department', 'Email', 'Status'];
            const data = rows.map(row => [
                row.full_name,
                row.department,
                row.email,
                row.status
            ]);
            
            doc.autoTable({
                startY: 40,
                head: [columns],
                body: data,
                theme: 'grid',
                styles: { fontSize: 10 }
            });
            
            doc.save('teachers_' + new Date().getTime() + '.pdf');
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
