<?php
/**
 * Questions Management - Enterprise Grade CRUD
 * Features: DataTables, Modal Form, Status Toggle, Admin Tracking
 */

require_once '../includes/helpers.php';
require_once '../config/database.php';

initializeSession();
requireLogin();

$success_msg = getSuccessMessage();
$error_msg = getErrorMessage();

// Define allowed statuses
const ALLOWED_STATUS = ['active', 'inactive'];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json');
    
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            jsonResponse(false, 'Security token invalid.', []);
        }
        
        $ajax_action = sanitizeInput($_POST['ajax_action']);
        
        // Handle: Get all questions for DataTables
        if ($ajax_action === 'get_questions') {
            $questions = $questions_collection->find([], ['sort' => ['question_order' => 1, 'created_at' => -1]])->toArray();
            $formatted = [];
            
            foreach ($questions as $question) {
                $question_id = objectIdToString($question['_id']);
                $status = $question['status'] ?? 'active';
                $status_badge = '<span class="status-badge status-' . $status . '">' 
                    . ($status === 'active' ? '✓ Active' : '✗ Inactive') 
                    . '</span>';
                
                // Build actions - edit available for all, delete only for admin
                $actions = '
                    <button class="btn btn-sm btn-outline-primary btn-edit-question" data-id="' . htmlspecialchars($question_id) . '" title="Edit">
                        <i class="bi bi-pencil"></i>
                    </button>';
                
                if (isAdmin()) {
                    $actions .= '
                    <button class="btn btn-sm btn-outline-danger btn-delete-question" data-id="' . htmlspecialchars($question_id) . '" title="Delete">
                        <i class="bi bi-trash"></i>
                    </button>';
                }
                $actions .= '
                ';
                
                $formatted[] = [
                    '_id' => $question_id,
                    'question_text' => $question['question_text'] ?? '',
                    'category' => $question['category'] ?? 'General',
                    'status' => $status,
                    'status_badge' => $status_badge,
                    'question_order' => $question['question_order'] ?? 0,
                    'created_at' => formatDateTime($question['created_at'] ?? ''),
                    'created_by' => $question['created_by'] ?? 'System',
                    'updated_at' => formatDateTime($question['updated_at'] ?? ''),
                    'updated_by' => $question['updated_by'] ?? 'System',
                    'actions' => $actions
                ];
            }
            
            jsonResponse(true, '', $formatted);
        }
        
        // Handle: Get single question for editing
        if ($ajax_action === 'get_question') {
            $question_id = sanitizeInput($_POST['question_id'] ?? '');
            
            if (!isValidObjectId($question_id)) {
                jsonResponse(false, 'Invalid question ID', []);
            }
            
            $question = $questions_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($question_id)]);
            
            if (!$question) {
                jsonResponse(false, 'Question not found', []);
            }
            
            jsonResponse(true, '', [
                '_id' => objectIdToString($question['_id']),
                'question_text' => $question['question_text'] ?? '',
                'category' => $question['category'] ?? 'General',
                'status' => $question['status'] ?? 'active',
                'question_order' => $question['question_order'] ?? 0
            ]);
        }
        
        // Handle: Add question
        if ($ajax_action === 'add_question') {
            $question_text = sanitizeInput($_POST['question_text'] ?? '');
            $category = sanitizeInput($_POST['category'] ?? 'General');
            $question_order = (int)($_POST['question_order'] ?? 0);
            $status = sanitizeInput($_POST['status'] ?? 'active');
            
            if (empty($question_text)) {
                jsonResponse(false, 'Question text is required', []);
            }
            
            if (strlen($question_text) < 5) {
                jsonResponse(false, 'Question text must be at least 5 characters', []);
            }
            
            if (!in_array($status, ALLOWED_STATUS)) {
                $status = 'active';
            }
            
            $result = $questions_collection->insertOne([
                'question_text' => $question_text,
                'category' => $category,
                'status' => $status,
                'question_order' => $question_order,
                'question_type' => 'rating',
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'created_by' => getLoggedInAdminUsername(),
                'updated_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_by' => getLoggedInAdminUsername()
            ]);
            
            logActivity('QUESTION_ADDED', 'Added question: ' . substr($question_text, 0, 50));
            jsonResponse(true, 'Question added successfully!', []);
        }
        
        // Handle: Update question
        if ($ajax_action === 'update_question') {
            $question_id = sanitizeInput($_POST['question_id'] ?? '');
            $question_text = sanitizeInput($_POST['question_text'] ?? '');
            $category = sanitizeInput($_POST['category'] ?? 'General');
            $question_order = (int)($_POST['question_order'] ?? 0);
            $status = sanitizeInput($_POST['status'] ?? 'active');
            
            if (!isValidObjectId($question_id)) {
                jsonResponse(false, 'Invalid question ID', []);
            }
            
            if (empty($question_text)) {
                jsonResponse(false, 'Question text is required', []);
            }
            
            if (strlen($question_text) < 5) {
                jsonResponse(false, 'Question text must be at least 5 characters', []);
            }
            
            if (!in_array($status, ALLOWED_STATUS)) {
                $status = 'active';
            }
            
            $questions_collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($question_id)],
                [
                    '$set' => [
                        'question_text' => $question_text,
                        'category' => $category,
                        'status' => $status,
                        'question_order' => $question_order,
                        'updated_at' => new MongoDB\BSON\UTCDateTime(),
                        'updated_by' => getLoggedInAdminUsername()
                    ]
                ]
            );
            
            logActivity('QUESTION_UPDATED', 'Updated question: ' . substr($question_text, 0, 50));
            jsonResponse(true, 'Question updated successfully!', []);
        }
        
        // Handle: Delete question
        if ($ajax_action === 'delete_question') {
            // Only admin can delete questions
            if (!isAdmin()) {
                jsonResponse(false, 'Access denied. Only administrators can delete questions.', []);
            }
            
            $question_id = sanitizeInput($_POST['question_id'] ?? '');
            
            if (!isValidObjectId($question_id)) {
                jsonResponse(false, 'Invalid question ID', []);
            }
            
            $question = $questions_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($question_id)]);
            
            if (!$question) {
                jsonResponse(false, 'Question not found', []);
            }
            
            $questions_collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($question_id)]);
            
            logActivity('QUESTION_DELETED', 'Deleted question: ' . substr($question['question_text'] ?? '', 0, 50));
            jsonResponse(true, 'Question deleted successfully!', []);
        }
        
        // Handle: Toggle question status
        if ($ajax_action === 'toggle_status') {
            $question_id = sanitizeInput($_POST['question_id'] ?? '');
            
            if (!isValidObjectId($question_id)) {
                jsonResponse(false, 'Invalid question ID', []);
            }
            
            $question = $questions_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($question_id)]);
            
            if (!$question) {
                jsonResponse(false, 'Question not found', []);
            }
            
            $current_status = $question['status'] ?? 'active';
            $new_status = $current_status === 'active' ? 'inactive' : 'active';
            
            $questions_collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($question_id)],
                [
                    '$set' => [
                        'status' => $new_status,
                        'updated_at' => new MongoDB\BSON\UTCDateTime(),
                        'updated_by' => getLoggedInAdminUsername()
                    ]
                ]
            );
            
            logActivity('QUESTION_STATUS_CHANGED', 'Question status changed to: ' . $new_status);
            jsonResponse(true, 'Question status updated!', ['status' => $new_status]);
        }
        
    } catch (\Exception $e) {
        jsonResponse(false, 'Error: ' . $e->getMessage(), []);
    }
    
    exit;
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questions Management - Teacher Evaluation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/teacher-eval/assets/css/dark-theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
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
        
        .question-badge {
            font-size: 11px;
            padding: 3px 8px;
        }
        
        #questionsTable_wrapper {
            margin-top: 20px;
        }

        body.dark-mode #questionsTable_wrapper {
            color: #e0e0e0;
        }

        body.dark-mode .dataTables_wrapper .dataTables_filter input {
            background-color: #1a1a1a;
            color: #e0e0e0;
            border-color: #444;
        }

        body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button {
            color: #e0e0e0;
        }

        body.dark-mode .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background-color: #667eea;
        }

        .modal-header {
            border-bottom: 2px solid #667eea;
        }

        body.dark-mode .modal-header {
            background: #2d2d2d;
            border-bottom-color: #667eea;
        }

        body.dark-mode .modal-body {
            background: #2d2d2d;
        }

        body.dark-mode .modal-footer {
            background: #2d2d2d;
            border-top-color: #444;
        }

        body.dark-mode .form-control,
        body.dark-mode .form-select {
            background-color: #1a1a1a;
            color: #e0e0e0;
            border-color: #444;
        }

        body.dark-mode .form-control:focus,
        body.dark-mode .form-select:focus {
            background-color: #1a1a1a;
            color: #e0e0e0;
            border-color: #667eea;
        }

        body.dark-mode .form-label {
            color: #e0e0e0;
        }

        .btn-group-vertical .btn {
            border-radius: 0;
        }

        .btn-group-vertical .btn:first-child {
            border-top-left-radius: 6px;
            border-top-right-radius: 6px;
        }

        .btn-group-vertical .btn:last-child {
            border-bottom-left-radius: 6px;
            border-bottom-right-radius: 6px;
        }

        /* Disable animations on table hover */
        .table tbody tr,
        .table tbody tr *,
        #questionsTable tbody tr,
        #questionsTable tbody tr * {
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
                <h1 class="h2"><i class="bi bi-question-circle"></i> Questions Management</h1>
                <p class="text-muted">Manage evaluation questions for teachers</p>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-outline-secondary me-2" onclick="exportQuestionsPDF()">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </button>
                <button class="btn btn-primary btn-lg" onclick="openQuestionModal()">
                    <i class="bi bi-plus-circle"></i> Add New Question
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
                                <th>Order</th>
                                <th>Question</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </div>  <!-- Close main-content -->

    <!-- Question Modal -->
    <div class="modal fade" id="questionModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Add New Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="questionForm">
                    <?php outputCSRFToken(); ?>
                    <input type="hidden" id="questionIdInput" name="question_id" value="">
                    <input type="hidden" id="ajaxActionInput" name="ajax_action" value="add_question">
                    
                    <div class="modal-body">
                        <!-- Question Order -->
                        <div class="mb-3">
                            <label for="question_order" class="form-label">Display Order</label>
                            <input 
                                type="number" 
                                class="form-control" 
                                id="question_order" 
                                name="question_order"
                                min="0"
                                max="100"
                                value="0"
                                placeholder="e.g., 1, 2, 3..."
                            >
                            <small class="form-text text-muted">Lower numbers appear first (0-100)</small>
                        </div>
                        
                        <!-- Question Text -->
                        <div class="mb-3">
                            <label for="question_text" class="form-label">Question Text *</label>
                            <textarea 
                                class="form-control" 
                                id="question_text" 
                                name="question_text"
                                rows="3"
                                placeholder="e.g., How well did the teacher explain the lesson?"
                                required
                            ></textarea>
                            <small class="form-text text-muted">This will be rated 1-5 (Poor to Excellent)</small>
                        </div>
                        
                        <!-- Category -->
                        <div class="mb-3">
                            <label for="question_category" class="form-label">Category</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="question_category" 
                                name="category"
                                placeholder="e.g., Teaching Quality, Communication, Engagement"
                                value="General"
                            >
                        </div>
                        
                        <!-- Status -->
                        <div class="mb-3">
                            <label for="question_status" class="form-label">Status</label>
                            <select class="form-select" id="question_status" name="status">
                                <option value="active">Active (Show in evaluations)</option>
                                <option value="inactive">Inactive (Hidden from evaluations)</option>
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

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="/teacher-eval/assets/js/main.js"></script>
    <script src="/teacher-eval/assets/js/questions.js"></script>
    <script src="/teacher-eval/assets/js/export-pdf.js"></script>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>

