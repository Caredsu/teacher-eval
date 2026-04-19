<?php
/**
 * Questions Management - Admin Console
 * Uses new API endpoints instead of direct database queries
 */

require_once '../includes/helpers.php';
require_once '../config/database.php';

initializeSession();
requireLogin();
requirePermission('manage_questions');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questions - Teacher Evaluation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dark-theme.css?v=2.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .type-badge {
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
        body.dark-mode .form-select,
        body.dark-mode .form-check-input {
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
            transition: none !important;
            animation-play-state: paused !important;
        }

        /* Icon-only button styles */
        .btn-icon {
            width: 36px;
            height: 36px;
            padding: 0 !important;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 1.5px solid #667eea;
            background: transparent !important;
            color: #667eea;
            font-size: 16px;
            transition: all 0.2s ease;
            margin: 0 4px;
        }

        .btn-icon:hover {
            background: #667eea !important;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-icon.deleteBtn {
            border-color: #dc3545;
            color: #dc3545;
        }

        .btn-icon.deleteBtn:hover {
            background: #dc3545 !important;
            color: white;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        body.dark-mode .btn-icon {
            border-color: #8b9eff;
            color: #8b9eff;
        }

        body.dark-mode .btn-icon:hover {
            background: #667eea !important;
            color: white;
        }

        body.dark-mode .btn-icon.deleteBtn {
            border-color: #ff6b6b;
            color: #ff6b6b;
        }

        body.dark-mode .btn-icon.deleteBtn:hover {
            background: #dc3545 !important;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 4px;
            justify-content: center;
        }
        
        /* Skeleton Loader Styles */
        .skeleton-loader {
            display: none !important;
            position: fixed;
            top: 70px;
            left: 0;
            right: 0;
            width: 100%;
            z-index: 999;
            background: #f8fafc;
        }
        
        .skeleton-loader.loading {
            display: block !important;
        }
        
        .skeleton-loader.loading ~ .content-loader {
            display: none !important;
        }
        
        .content-loader {
            display: block !important;
            opacity: 1;
            transition: opacity 0.3s ease;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>
    
    <!-- Skeleton Loader -->
    <div class="skeleton-loader loading" data-show-skeleton="true">
        <div class="container-fluid py-5">
            <div class="row mb-4">
                <div class="col-md-6">
                    <div style="height: 30px; background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%); background-size: 200% 100%; animation: skeleton-loading 1.5s infinite; border-radius: 4px; margin-bottom: 10px;"></div>
                    <div style="height: 16px; width: 60%; background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%); background-size: 200% 100%; animation: skeleton-loading 1.5s infinite; border-radius: 4px;"></div>
                </div>
            </div>
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div style="height: 40px; background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%); background-size: 200% 100%; animation: skeleton-loading 1.5s infinite; border-radius: 4px; margin-bottom: 15px;"></div>
                    <div style="height: 300px; background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%); background-size: 200% 100%; animation: skeleton-loading 1.5s infinite; border-radius: 4px;"></div>
                </div>
            </div>
        </div>
        <style>
            @keyframes skeleton-loading {
                0% { background-position: 200% 0; }
                100% { background-position: -200% 0; }
            }
        </style>
    </div>
    
    <!-- Main Content Wrapper -->
    <div class="content-loader active">
        <div class="container-fluid py-5">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h1 class="h2"><i class="bi bi-question-circle"></i> Questions Management</h1>
                    <p class="text-muted">Manage evaluation questions and categories</p>
                </div>
                <div class="col-md-6 text-end">
                    <button class="btn btn-outline-secondary me-2" onclick="printQuestionsTable()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                    <button class="btn btn-primary btn-lg" onclick="openQuestionModal()">
                        <i class="bi bi-plus-circle"></i> Add New Question
                    </button>
                </div>
            </div>

            <!-- Questions Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">All Questions</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="questionsTable" class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Question</th>
                                    <th>Order</th>
                                    <th>Required</th>
                                    <th>Status</th>
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

    <!-- Question Modal -->
    <div class="modal fade" id="questionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="questionForm">
                    <div class="modal-body">
                        <!-- Question Text -->
                        <div class="mb-3">
                            <label for="question_text" class="form-label">Question Text *</label>
                            <textarea 
                                class="form-control" 
                                id="question_text" 
                                name="question_text"
                                placeholder="Enter the question text"
                                rows="3"
                                required
                            ></textarea>
                        </div>

                        <!-- Display Order -->
                        <div class="mb-3">
                            <label for="display_order" class="form-label">Display Order (Auto)</label>
                            <input 
                                type="number" 
                                class="form-control" 
                                id="display_order" 
                                name="display_order"
                                min="1"
                                readonly
                            >
                            <small class="text-muted d-block mt-1">Automatically assigned based on question count</small>
                        </div>

                        <!-- Required Checkbox -->
                        <div class="mb-3 form-check">
                            <input 
                                type="checkbox" 
                                class="form-check-input" 
                                id="required" 
                                name="required"
                            >
                            <label class="form-check-label" for="required">
                                Required Question
                            </label>
                        </div>

                        <!-- Status -->
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active">Active (show in evaluation)</option>
                                <option value="inactive">Inactive (hide in evaluation)</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Save Question
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
    <script src="<?= ASSETS_URL ?>/js/main.js"></script>
    <script src="<?= ASSETS_URL ?>/js/api-service.js"></script>
    
    <!-- Expose user role to JavaScript -->
    <script>
        const currentUserRole = '<?php echo getUserRole(); ?>';
    </script>

    <script>
        // Questions Management - Uses APIService for all operations
        const questionModal = new bootstrap.Modal(document.getElementById('questionModal'), {});
        let questionsTable = null;
        
        document.addEventListener('DOMContentLoaded', () => {
            const skeletonLoader = document.querySelector('.skeleton-loader');
            if (skeletonLoader) {
                setTimeout(function() {
                    skeletonLoader.classList.remove('loading');
                }, 300);
            }
            initializeQuestionsTable();
            bindFormEvents();
        });
        
        function initializeQuestionsTable() {
            questionsTable = $('#questionsTable').DataTable({
                processing: true,
                serverSide: false,
                columns: [
                    { data: 'question_text', title: 'Question' },
                    { data: 'display_order', title: 'Order' },
                    {
                        data: 'required',
                        title: 'Required',
                        render: function(data) {
                            return data ? '✓ Yes' : '✗ No';
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
                        data: 'id',
                        title: 'Actions',
                        orderable: false,
                        render: function(data, type, row) {
                            let actions = `
                                <div class="action-buttons">
                                    <button class="btn btn-icon editBtn" data-question-id="${data}" data-bs-toggle="tooltip" data-bs-title="Edit">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>`;
                            
                            // Only show delete button for admin
                            if (currentUserRole === 'admin') {
                                actions += `<button class="btn btn-icon deleteBtn" data-question-id="${data}" data-bs-toggle="tooltip" data-bs-title="Delete">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>`;
                            }
                            
                            actions += `</div>`;
                            return actions;
                        }
                    }
                ],
                order: [[1, 'asc']],
                pageLength: 10,
                language: {
                    emptyTable: "No questions found"
                }
            });
            
            loadQuestions();
        }
        
        function loadQuestions() {
            api.getQuestions()
                .then(response => {
                    console.log('Questions response:', response);
                    if (response.success) {
                        // Handle paginated response or raw array
                        let data = response.data;
                        if (data && data.data && Array.isArray(data.data)) {
                            data = data.data;  // Extract from paginated wrapper
                        } else if (!Array.isArray(data)) {
                            data = [];
                        }
                        
                        questionsTable.clear().rows.add(data).draw();
                        attachRowEventHandlers();
                        initializeTooltips();
                    } else {
                        showError(response.message || 'Failed to load questions');
                    }
                })
                .catch(error => {
                    console.error('Error loading questions:', error);
                    showError('Error loading questions: ' + error.message);
                });
        }
        
        function attachRowEventHandlers() {
            $('#questionsTable tbody').off('click'); // Remove previous handlers
            
            $('#questionsTable tbody').on('click', '.editBtn', function() {
                const questionId = $(this).data('question-id');
                editQuestion(questionId);
            });
            
            $('#questionsTable tbody').on('click', '.deleteBtn', function() {
                const questionId = $(this).data('question-id');
                deleteQuestion(questionId);
            });
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
        
        function openQuestionModal() {
            document.getElementById('questionForm').reset();
            document.getElementById('modalTitle').textContent = 'Add New Question';
            delete document.getElementById('questionForm').dataset.questionId;
            
            // Auto-calculate next display order
            const nextOrder = questionsTable.data().length + 1;
            document.getElementById('display_order').value = nextOrder;
            document.getElementById('display_order').readOnly = true;
            
            questionModal.show();
        }
        
        function editQuestion(questionId) {
            api.getQuestion(questionId)
                .then(response => {
                    if (response.success && response.data) {
                        const question = response.data;
                        document.getElementById('question_text').value = question.question_text || '';
                        document.getElementById('display_order').value = question.display_order || '';
                        document.getElementById('required').checked = question.required || false;
                        document.getElementById('status').value = question.status || 'active';
                        
                        // Allow editing order when modifying existing question
                        document.getElementById('display_order').readOnly = false;
                        document.getElementById('questionForm').dataset.questionId = questionId;
                        document.getElementById('modalTitle').textContent = 'Edit Question';
                        questionModal.show();
                    } else {
                        showError('Failed to load question details');
                    }
                })
                .catch(error => showError('Error: ' + error.message));
        }
        
        function deleteQuestion(questionId) {
            Swal.fire({
                title: 'Delete Question?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel'
            }).then(result => {
                if (result.isConfirmed) {
                    api.deleteQuestion(questionId)
                        .then(response => {
                            if (response.success) {
                                showSuccess(response.message || 'Question deleted successfully');
                                loadQuestions();
                            } else {
                                showError(response.message || 'Failed to delete question');
                            }
                        })
                        .catch(error => showError('Error: ' + error.message));
                }
            });
        }
        
        function bindFormEvents() {
            document.getElementById('questionForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                const questionId = this.dataset.questionId;
                const isEditing = !!questionId;
                const questionText = document.getElementById('question_text').value.trim();
                const displayOrder = parseInt(document.getElementById('display_order').value);
                const required = document.getElementById('required').checked;
                const status = document.getElementById('status').value;
                
                // Clear previous errors
                document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
                document.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
                
                const data = {
                    question_text: questionText,
                    display_order: displayOrder,
                    required: required,
                    status: status
                };
                
                if (isEditing) {
                    api.updateQuestion(questionId, data)
                        .then(response => {
                            if (response.success) {
                                showSuccess(response.message || 'Question updated successfully');
                                questionModal.hide();
                                delete this.dataset.questionId;
                                loadQuestions();
                            } else {
                                handleValidationErrors(response);
                                showError(response.message || 'Failed to update question');
                            }
                        })
                        .catch(error => showError('Error: ' + error.message));
                } else {
                    api.createQuestion(data)
                        .then(response => {
                            if (response.success) {
                                showSuccess(response.message || 'Question created successfully');
                                questionModal.hide();
                                loadQuestions();
                            } else {
                                handleValidationErrors(response);
                                showError(response.message || 'Failed to create question');
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
                    'question_text': 'question_text',
                    'category': 'category',
                    'type': 'type',
                    'display_order': 'display_order',
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
        
        function printQuestionsTable() {
            const rows = questionsTable.rows({ search: 'applied' }).data().toArray();
            
            let printContent = '<html><head><meta charset="UTF-8"><title>Questions Report</title>';
            printContent += '<style>';
            printContent += 'body { font-family: Arial, sans-serif; margin: 20px; background: white; }';
            printContent += 'h1 { text-align: center; color: #333; margin-bottom: 30px; }';
            printContent += 'table { width: 100%; border-collapse: collapse; margin-top: 20px; }';
            printContent += 'th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }';
            printContent += 'th { background: #667eea; color: white; font-weight: bold; }';
            printContent += 'tr:nth-child(even) { background: #f9f9f9; }';
            printContent += '.timestamp { text-align: center; color: #666; margin-top: 20px; font-size: 12px; }';
            printContent += '@media print { body { margin: 0; } }';
            printContent += '</style></head><body>';
            
            printContent += '<h1>Questions Report</h1>';
            printContent += '<p style="text-align: center; color: #666;">Generated: ' + new Date().toLocaleString() + '</p>';
            printContent += '<table><thead><tr>';
            printContent += '<th>Question</th><th>Order</th><th>Required</th><th>Status</th>';
            printContent += '</tr></thead><tbody>';
            
            rows.forEach(row => {
                const statusBadge = row.status === 'active' ? 
                    '<span style="background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 3px;">ACTIVE</span>' :
                    '<span style="background: #f8d7da; color: #721c24; padding: 4px 8px; border-radius: 3px;">INACTIVE</span>';
                
                printContent += '<tr>';
                printContent += '<td>' + (row.question_text || '') + '</td>';
                printContent += '<td>' + (row.display_order || '') + '</td>';
                printContent += '<td>' + (row.required ? 'Yes' : 'No') + '</td>';
                printContent += '<td>' + statusBadge + '</td>';
                printContent += '</tr>';
            });
            
            printContent += '</tbody></table>';
            printContent += '<div class="timestamp">Total Questions: ' + rows.length + '</div>';
            printContent += '</body></html>';
            
            const printWindow = window.open('', '', 'height=600,width=900');
            printWindow.document.write(printContent);
            printWindow.document.close();
            setTimeout(() => {
                printWindow.print();
            }, 250);
        }
    </script>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>
