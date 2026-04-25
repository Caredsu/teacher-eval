/**
 * Teachers Page JavaScript
 * Specific functionality for admin/teachers.php
 */

// Teachers DataTable
let teachersTable = null;

// Initialize Teachers Page
function initializeTeachersPage() {
    initializeTeachersTable();
    setupTeacherFilters();
}

// Initialize DataTable for Teachers
function initializeTeachersTable() {
    const teachersTableElement = document.getElementById('teachersTable');
    if (!teachersTableElement || typeof $ === 'undefined') return;
    
    teachersTable = $('#teachersTable').DataTable({
        responsive: true,
        paging: true,
        pageLength: 10,
        ordering: true,
        searching: true,
        language: {
            search: 'Filter teachers:',
            lengthMenu: 'Show _MENU_ teachers per page',
            info: 'Showing _START_ to _END_ of _TOTAL_ teachers'
        },
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>t<"row"<"col-md-6"i><"col-md-6"p>>'
    });
}

// Setup Teacher Filters
function setupTeacherFilters() {
    const departmentFilter = document.getElementById('departmentFilter');
    if (departmentFilter) {
        departmentFilter.addEventListener('change', applyTeacherFilters);
    }
    
    const statusFilter = document.getElementById('statusFilter');
    if (statusFilter) {
        statusFilter.addEventListener('change', applyTeacherFilters);
    }
}

// Apply Teacher Filters
function applyTeacherFilters() {
    const department = document.getElementById('departmentFilter')?.value;
    const status = document.getElementById('statusFilter')?.value;
    
    const filters = {
        department: department || '',
        status: status || ''
    };
    
    loadTeachersData(filters);
}

// Load Teachers Data
function loadTeachersData(filters = {}) {
    const queryString = new URLSearchParams(filters).toString();
    const url = `/teacher-eval/admin/teachers.php?load_data=1&${queryString}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && teachersTable) {
                teachersTable.clear();
                if (Array.isArray(data.teachers)) {
                    data.teachers.forEach(teacher => {
                        const initial = teacher.name?.charAt(0)?.toUpperCase() || '?';
                        const avatar = `<span class="teacher-avatar">${initial}</span>`;
                        const departmentBadge = `<span class="department-badge">${escapeHtml(teacher.department)}</span>`;
                        const statusClass = teacher.status === 'active' ? 'teacher-status-active' : 'teacher-status-inactive';
                        const statusBadge = `<span class="${statusClass}">${teacher.status}</span>`;
                        
                        teachersTable.row.add([
                            avatar + ' ' + escapeHtml(teacher.name),
                            escapeHtml(teacher.email),
                            departmentBadge,
                            statusBadge,
                            `<button class="btn btn-sm btn-outline-primary" onclick="editTeacher(${teacher.id})">Edit</button>
                             <button class="btn btn-sm btn-outline-danger" onclick="deleteTeacher(${teacher.id})">Delete</button>`
                        ]);
                    });
                }
                teachersTable.draw();
            }
        })
        .catch(error => {
            console.error('Error loading teachers:', error);
        });
}

// Edit Teacher
function editTeacher(teacherId) {
    fetch(`/teacher-eval/api/teachers.php?id=${teacherId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showEditTeacherModal(data.teacher);
            }
        });
}

// Show Edit Teacher Modal
function showEditTeacherModal(teacher) {
    const modal = document.getElementById('editTeacherModal');
    if (!modal) return;
    
    // Populate form fields
    document.getElementById('teacherId').value = teacher.id;
    document.getElementById('teacherName').value = teacher.name || '';
    document.getElementById('teacherEmail').value = teacher.email || '';
    document.getElementById('teacherDepartment').value = teacher.department || '';
    document.getElementById('teacherStatus').value = teacher.status || 'active';
    
    // Show modal
    if (typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

// Delete Teacher
function deleteTeacher(teacherId) {
    showConfirm('Delete Teacher', 'Are you sure you want to delete this teacher?', () => {
        fetch(`/teacher-eval/api/teachers.php?id=${teacherId}`, {
            method: 'DELETE'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess('Teacher Deleted', 'Teacher has been deleted successfully');
                if (teachersTable) {
                    teachersTable.ajax.reload();
                }
            } else {
                showError('Error', data.message || 'Failed to delete teacher');
            }
        });
    });
}

// Save Teacher Changes
function saveTeacherChanges() {
    const form = document.getElementById('editTeacherForm');
    const formData = new FormData(form);
    const teacherId = document.getElementById('teacherId').value;
    
    fetch(`/teacher-eval/api/teachers.php?id=${teacherId}`, {
        method: 'PUT',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Teacher Updated', 'Teacher has been updated successfully');
            bootstrap.Modal.getInstance(document.getElementById('editTeacherModal')).hide();
            if (teachersTable) {
                teachersTable.ajax.reload();
            }
        } else {
            showError('Error', data.message || 'Failed to update teacher');
        }
    });
}

// Initialize on DOM Ready
document.addEventListener('DOMContentLoaded', initializeTeachersPage);
