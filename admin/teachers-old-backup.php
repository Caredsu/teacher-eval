<?php
/**
 * Teachers Management CRUD - Refactored Version
 * Features: Department dropdown validation, Status field, Modern UI
 */

require_once '../includes/helpers.php';
require_once '../config/database.php';

initializeSession();
requireLogin();

// Define allowed departments (ONLY these 4)
const ALLOWED_DEPARTMENTS = ['ECT', 'EDUC', 'CCJE', 'BHT'];
const ALLOWED_STATUS = ['active', 'inactive'];

$success_msg = getSuccessMessage();
$error_msg = getErrorMessage();
$action = getGET('action', '');
$edit_id = getGET('id', '');

// ==================== AJAX GET TEACHERS DATA ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'get_teachers') {
    header('Content-Type: application/json');
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'CSRF token invalid']);
        exit;
    }
    
    try {
        $draw = intval($_POST['draw'] ?? 0);
        $start = intval($_POST['start'] ?? 0);
        $length = intval($_POST['length'] ?? 10);
        $search = trim($_POST['search']['value'] ?? '');
        
        // Build search filter
        $filter = [];
        if (!empty($search)) {
            $filter['$or'] = [
                ['first_name' => ['$regex' => $search, '$options' => 'i']],
                ['last_name' => ['$regex' => $search, '$options' => 'i']],
                ['email' => ['$regex' => $search, '$options' => 'i']],
                ['department' => ['$regex' => $search, '$options' => 'i']]
            ];
        }
        
        $total_records = $teachers_collection->countDocuments();
        $filtered_records = $teachers_collection->countDocuments($filter);
        
        $teachers = $teachers_collection->find(
            $filter,
            [
                'sort' => ['created_at' => -1],
                'skip' => $start,
                'limit' => $length
            ]
        );
        
        $data = [];
        foreach ($teachers as $teacher) {
            $teacher_id = objectIdToString($teacher['_id']);
            $full_name = formatFullName(
                (string)($teacher['first_name'] ?? ''),
                (string)($teacher['middle_name'] ?? ''),
                (string)($teacher['last_name'] ?? '')
            );
            $dept = $teacher['department'] ?? 'N/A';
            $status = $teacher['status'] ?? 'active';
            
            // Build actions based on user role
            $actions = '<button class="btn btn-sm btn-outline-primary btn-edit-teacher" data-id="' . escapeOutput($teacher_id) . '" title="Edit Teacher"><i class="bi bi-pencil"></i></button>';
            if (isAdmin()) {
                $actions .= ' <a href="#" class="btn btn-sm btn-outline-danger btn-delete-teacher" onclick="return false;" data-id="' . escapeOutput($teacher_id) . '" data-name="' . escapeOutput($full_name) . '" title="Delete Teacher"><i class="bi bi-trash"></i></a>';
            }
            
            $data[] = [
                'name' => '<i class="bi bi-person-circle"></i> ' . escapeOutput($full_name),
                'department' => '<span class="dept-badge dept-' . strtolower($dept) . '">' . escapeOutput($dept) . '</span>',
                'email' => escapeOutput($teacher['email'] ?? '-'),
                'status_badge' => '<span class="status-badge status-' . $status . '">' . ($status === 'active' ? '✓ Active' : '✗ Inactive') . '</span>',
                'created_at' => formatDateTime($teacher['created_at'] ?? ''),
                'updated_at' => formatDateTimeWithUser($teacher['updated_at'] ?? '', $teacher['updated_by'] ?? ''),
                'actions' => $actions
            ];
        }
        
        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $total_records,
            'recordsFiltered' => $filtered_records,
            'data' => $data
        ]);
        exit;
        
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// ==================== AJAX GET TEACHER DATA (BEFORE CSRF CHECK) ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_teacher_data'])) {
    header('Content-Type: application/json');
    
    $teacher_id = trim($_POST['get_teacher_data']);
    
    if (!isValidObjectId($teacher_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid teacher ID']);
        exit;
    }
    
    try {
        $teacher = $teachers_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($teacher_id)]);
        
        if (!$teacher) {
            echo json_encode(['success' => false, 'message' => 'Teacher not found']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'teacher' => [
                '_id' => objectIdToString($teacher['_id']),
                'first_name' => $teacher['first_name'] ?? '',
                'middle_name' => $teacher['middle_name'] ?? '',
                'last_name' => $teacher['last_name'] ?? '',
                'department' => $teacher['department'] ?? '',
                'email' => $teacher['email'] ?? '',
                'status' => $teacher['status'] ?? 'active'
            ]
        ]);
        exit;
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

// ==================== FORM SUBMISSION HANDLING ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken(getPOST('csrf_token'))) {
        setErrorMessage('Security token invalid.');
        redirect('/teacher-eval/admin/teachers.php');
    }
    
    $action_type = getPOST('action_type');
    $first_name = trim(getPOST('first_name', ''));
    $middle_name = trim(getPOST('middle_name', ''));
    $last_name = trim(getPOST('last_name', ''));
    $department = trim(getPOST('department', ''));
    $email = trim(getPOST('email', ''));
    $status = getPOST('status', 'active');
    
    // ===== VALIDATION =====
    $errors = [];
    
    // Validate first name
    if (empty($first_name)) {
        $errors[] = 'First name is required.';
    } elseif (strlen($first_name) < 2) {
        $errors[] = 'First name must be at least 2 characters.';
    }
    
    // Validate last name
    if (empty($last_name)) {
        $errors[] = 'Last name is required.';
    } elseif (strlen($last_name) < 2) {
        $errors[] = 'Last name must be at least 2 characters.';
    }
    
    // Validate middle name (optional, but if provided check length)
    if (!empty($middle_name) && strlen($middle_name) < 1) {
        $errors[] = 'Middle name must be at least 1 character.';
    }
    
    // Validate department (MUST be one of the allowed values)
    if (empty($department)) {
        $errors[] = 'Department is required.';
    } elseif (!in_array($department, ALLOWED_DEPARTMENTS)) {
        $errors[] = 'Invalid department selected. Allowed: ' . implode(', ', ALLOWED_DEPARTMENTS);
    }
    
    // Validate email (if provided)
    if (!empty($email) && !isValidEmail($email)) {
        $errors[] = 'Invalid email format.';
    }
    
    // Validate status
    if (!in_array($status, ALLOWED_STATUS)) {
        $errors[] = 'Invalid status selected.';
    }
    
    // Check for duplicate email (only if provided and adding new teacher)
    if (!empty($email) && $action_type === 'add') {
        $existing_email = $teachers_collection->findOne(['email' => $email]);
        if ($existing_email) {
            $errors[] = 'A teacher with this email already exists.';
        }
    }
    
    // Check for duplicate email when editing (exclude current teacher)
    if (!empty($email) && $action_type === 'edit') {
        $teacher_id = getPOST('teacher_id');
        $existing_email = $teachers_collection->findOne([
            'email' => $email,
            '_id' => ['$ne' => new MongoDB\BSON\ObjectId($teacher_id)]
        ]);
        if ($existing_email) {
            $errors[] = 'This email is already used by another teacher.';
        }
    }
    
    // Check for duplicate full name (case-insensitive) - only for adds
    if ($action_type === 'add') {
        $duplicate_name = $teachers_collection->findOne([
            'first_name' => ['$regex' => '^' . preg_quote($first_name) . '$', '$options' => 'i'],
            'last_name' => ['$regex' => '^' . preg_quote($last_name) . '$', '$options' => 'i']
        ]);
        if ($duplicate_name) {
            $errors[] = 'A teacher with this first and last name already exists.';
        }
    }
    
    if (!empty($errors)) {
        $error_msg = implode(' | ', $errors);
        // Return JSON for AJAX, or redirect for regular form submissions
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error_msg]);
            exit;
        } else {
            setErrorMessage($error_msg);
            redirect('/teacher-eval/admin/teachers.php');
        }
    } else {
        try {
            if ($action_type === 'add') {
                // ===== ADD NEW TEACHER =====
                $result = $teachers_collection->insertOne([
                    'first_name' => $first_name,
                    'middle_name' => $middle_name,
                    'last_name' => $last_name,
                    'department' => $department,
                    'email' => $email,
                    'status' => $status,
                    'created_at' => new MongoDB\BSON\UTCDateTime(),
                    'created_by' => getLoggedInAdminUsername(),
                    'updated_at' => new MongoDB\BSON\UTCDateTime(),
                    'updated_by' => getLoggedInAdminUsername()
                ]);
                
                $full_name = formatFullName($first_name, $middle_name, $last_name);
                $message = '✓ Teacher added successfully!';
                logActivity('ADD_TEACHER', "Added by " . getLoggedInAdminUsername() . ": $full_name (Dept: $department)");
                
                // Return JSON for AJAX, or set message and redirect for regular submissions
                if (isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => $message]);
                    exit;
                } else {
                    setSuccessMessage($message);
                }
                
            } elseif ($action_type === 'edit') {
                // ===== EDIT EXISTING TEACHER =====
                $teacher_id = getPOST('teacher_id');
                
                if (!isValidObjectId($teacher_id)) {
                    $error_msg = 'Invalid teacher ID.';
                    if (isAjaxRequest()) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $error_msg]);
                        exit;
                    } else {
                        setErrorMessage($error_msg);
                        redirect('/teacher-eval/admin/teachers.php');
                    }
                }
                
                $result = $teachers_collection->updateOne(
                    ['_id' => new MongoDB\BSON\ObjectId($teacher_id)],
                    [
                        '$set' => [
                            'first_name' => $first_name,
                            'middle_name' => $middle_name,
                            'last_name' => $last_name,
                            'department' => $department,
                            'email' => $email,
                            'status' => $status,
                            'updated_at' => new MongoDB\BSON\UTCDateTime(),
                            'updated_by' => getLoggedInAdminUsername()
                        ]
                    ]
                );
                
                $full_name = formatFullName($first_name, $middle_name, $last_name);
                $message = '✓ Teacher updated successfully!';
                logActivity('UPDATE_TEACHER', "Updated by " . getLoggedInAdminUsername() . ": $full_name (Dept: $department)");
                
                // Return JSON for AJAX, or set message and redirect for regular submissions
                if (isAjaxRequest()) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => $message]);
                    exit;
                } else {
                    setSuccessMessage($message);
                }
            }
            
            // Redirect for non-AJAX requests
            if (!isAjaxRequest()) {
                redirect('/teacher-eval/admin/teachers.php');
            }
            
        } catch (\Exception $e) {
            $error_msg = 'Error: ' . $e->getMessage();
            if (isAjaxRequest()) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $error_msg]);
                exit;
            } else {
                setErrorMessage($error_msg);
            }
        }
    }
}

// ==================== DELETE HANDLING ====================
if (getGET('delete') && isValidObjectId(getGET('delete'))) {
    // Only admin can delete teachers
    if (!isAdmin()) {
        setErrorMessage('Access denied. Only administrators can delete teachers.');
    } elseif (!verifyCSRFToken(getGET('csrf'))) {
        setErrorMessage('Security token invalid.');
    } else {
        try {
            $delete_id = getGET('delete');
            $teacher = $teachers_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($delete_id)]);
            
            $teachers_collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($delete_id)]);
            
            $deleted_name = formatFullName(
                (string)($teacher['first_name'] ?? ''),
                (string)($teacher['middle_name'] ?? ''),
                (string)($teacher['last_name'] ?? '')
            );
            
            setSuccessMessage('✓ Teacher deleted successfully!');
            logActivity('DELETE_TEACHER', "Deleted by " . getLoggedInAdminUsername() . ": $deleted_name");
        } catch (\Exception $e) {
            setErrorMessage('Error deleting teacher: ' . $e->getMessage());
        }
    }
    redirect('/teacher-eval/admin/teachers.php');
}

// ==================== DATA RETRIEVAL ====================
// Pagination settings
$per_page = 10;
$current_page = max(1, (int)getGET('page', 1));
$offset = ($current_page - 1) * $per_page;

// Get all teachers (unsorted first)
try {
    $all_teachers_cursor = $teachers_collection->find([], ['sort' => ['created_at' => -1]]);
    $all_teachers = [];
    foreach ($all_teachers_cursor as $teacher) {
        $all_teachers[] = $teacher;
    }
} catch (\MongoDB\Driver\Exception\ConnectionTimeoutException $e) {
    die('Database Connection Timeout: MongoDB server is not responding. Please ensure MongoDB is running on localhost:27017');
} catch (\Exception $e) {
    die('Database Error: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES));
}

$total_teachers = count($all_teachers);
$total_pages = ceil($total_teachers / $per_page);
$current_page = min($current_page, max(1, $total_pages)); // Ensure page is within bounds

// Get teachers for current page
$teachers = array_slice($all_teachers, $offset, $per_page);

// Get teacher to edit
$edit_teacher = null;
if ($action === 'edit' && isValidObjectId($edit_id)) {
    $edit_teacher = $teachers_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($edit_id)]);
}

// ==================== DEPARTMENT STATISTICS ====================
$dept_stats = [];
foreach (ALLOWED_DEPARTMENTS as $dept) {
    $dept_stats[$dept] = $teachers_collection->countDocuments(['department' => $dept]);
}

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
        .teacher-form-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-icon {
            position: absolute;
            right: 12px;
            top: 28px;
            color: #667eea;
            pointer-events: none;
        }
        
        .form-floating input,
        .form-floating select {
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            padding-right: 36px;
        }
        
        .form-floating input:focus,
        .form-floating select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 10px 24px;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #5568d3 0%, #6a408c 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
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
        
        .stats-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            margin-bottom: 15px;
        }
        
        .stats-card h3 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
        }
        
        .stats-card p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        
        .table-hover tbody tr,
        .table-hover tbody tr * {
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
        
        .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.1) !important;
            animation: none !important;
            transition: none !important;
            animation-play-state: paused !important;
        }
        
        .filter-container {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }

        body.dark-mode .form-floating input,
        body.dark-mode .form-floating select {
            background: #1a1a1a;
            color: #e0e0e0;
            border-color: #444;
        }

        body.dark-mode .form-label {
            color: #e0e0e0;
        }

        body.dark-mode .filter-container {
            background: #2d2d2d;
        }

        body.dark-mode .form-icon {
            color: #667eea;
        }

        /* Modal Styles */
        .modal-content {
            border: none;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            border-radius: 12px;
        }

        body.dark-mode .modal-content {
            background: #1a1a1a;
            color: #e0e0e0;
        }

        .modal-header {
            border-bottom: 1px solid rgba(0,0,0,0.1);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        body.dark-mode .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-bottom: 1px solid #444;
        }

        .modal-title {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-close {
            filter: brightness(0) invert(1);
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>
    
    <!-- Main Content Wrapper -->
    <div class="main-content">
        <div class="container-fluid py-4">
            <!-- Header -->
            <div class="row mb-4">
            <div class="col-md-6">
                <h1 class="h2"><i class="bi bi-people-fill"></i> Teachers Management</h1>
                <p class="text-muted">Manage teacher information and assignments</p>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-outline-secondary me-2" onclick="exportTeachersPDF()">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </button>
                <button class="btn btn-primary" onclick="openTeacherModal()">
                    <i class="bi bi-plus-lg"></i> Add New Teacher
                </button>
            </div>
        </div>

        <!-- Department Stats - Simple Cards -->
        <div class="row g-3 mb-4">
            <?php 
            $dept_colors = [
                'ECT' => ['color' => '#667eea', 'icon' => '👨‍💼'],
                'EDUC' => ['color' => '#764ba2', 'icon' => '📚'],
                'CCJE' => ['color' => '#3498db', 'icon' => '⚖️'],
                'BHT' => ['color' => '#27ae60', 'icon' => '❤️']
            ];
            foreach (ALLOWED_DEPARTMENTS as $dept): 
            ?>
                <div class="col-lg-3 col-md-6">
                    <div style="background: #2c3e50; border: 1px solid #3d5066; border-radius: 10px; padding: 20px; border-left: 4px solid <?= $dept_colors[$dept]['color'] ?>; display: flex; justify-content: space-between; align-items: flex-start;">
                        <div>
                            <p style="margin: 0 0 8px 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;"><?= $dept ?></p>
                            <h2 style="margin: 0; font-size: 28px; font-weight: 700; color: #ffffff;"><?= $dept_stats[$dept] ?></h2>
                            <p style="margin: 5px 0 0 0; color: #bbb; font-size: 13px;">Teachers</p>
                        </div>
                        <div style="font-size: 32px; opacity: 0.5;"><?= $dept_colors[$dept]['icon'] ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>



        <!-- Search & Filter Bar (Now inside table card) -->

        <!-- Teachers Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-0">
                            <i class="bi bi-table"></i> All Teachers 
                            <span class="badge bg-primary"><?= $total_teachers ?></span>
                        </h5>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Pagination Summary -->
                <?php if ($total_teachers > 0): ?>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0" id="teachersTable">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teachers as $teacher): ?>
                                <?php 
                                $teacher_id = objectIdToString($teacher['_id']);
                                $dept = $teacher['department'] ?? 'N/A';
                                $status = $teacher['status'] ?? 'active';
                                $full_name = formatFullName(
                                    $teacher['first_name'] ?? '',
                                    $teacher['middle_name'] ?? '',
                                    $teacher['last_name'] ?? ''
                                );
                                ?>
                                <tr>
                                    <td class="fw-600">
                                        <i class="bi bi-person-circle"></i> <?= escapeOutput($full_name) ?>
                                    </td>
                                    <td>
                                        <span class="dept-badge dept-<?= strtolower($dept) ?>">
                                            <?= escapeOutput($dept) ?>
                                        </span>
                                    </td>
                                    <td><?= escapeOutput($teacher['email'] ?? '-') ?></td>
                                    <td>
                                        <span class="status-badge status-<?= $status ?>">
                                            <?= $status === 'active' ? '✓ Active' : '✗ Inactive' ?>
                                        </span>
                                    </td>
                                    <td><?= formatDateTime($teacher['created_at'] ?? '') ?></td>
                                    <td><?= formatDateTimeWithUser($teacher['updated_at'] ?? '', $teacher['updated_by'] ?? '') ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary btn-edit"
                                           onclick="editTeacher('<?= escapeOutput($teacher_id) ?>')" 
                                           title="Edit Teacher">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="#" 
                                           class="btn btn-sm btn-outline-danger btn-delete"
                                           onclick="return false;"
                                           data-delete-url="/teacher-eval/admin/teachers.php?delete=<?= escapeOutput($teacher_id) ?>&csrf=<?= escapeOutput(generateCSRFToken()) ?>"
                                           data-teacher-name="<?= escapeOutput($full_name) ?>">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination Controls -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4 border-top pt-3">
                    <ul class="pagination justify-content-center mb-0">
                        <!-- Previous Button -->
                        <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= max(1, $current_page - 1) ?>">
                                <i class="bi bi-chevron-left"></i> Previous
                            </a>
                        </li>
                        
                        <!-- Page Numbers -->
                        <?php
                        // Show max 5 page numbers
                        $start_page = max(1, $current_page - 2);
                        $end_page = min($total_pages, $current_page + 2);
                        
                        if ($start_page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1">1</a>
                            </li>
                            <?php if ($start_page > 2): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?>">
                                <?= $i ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $total_pages ?>"><?= $total_pages ?></a>
                            </li>
                        <?php endif; ?>
                        
                        <!-- Next Button -->
                        <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= min($total_pages, $current_page + 1) ?>">
                                Next <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
                
                <!-- Pagination Info -->
                <div class="text-center mt-3 text-muted">
                    <small>
                        Showing <?= $total_teachers > 0 ? $offset + 1 : 0 ?> - <?= min($offset + $per_page, $total_teachers) ?> 
                        of <?= $total_teachers ?> teacher(s) • Page <?= $current_page ?> of <?= $total_pages ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>  <!-- Close main-content -->

    <!-- Teacher Form Modal -->
    <div class="modal fade" id="teacherModal" tabindex="-1" aria-labelledby="teacherModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="teacherModalLabel">
                        <i class="bi bi-plus-square"></i>
                        <span id="modalTitleText">Add New Teacher</span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" id="teacherForm">
                        <?php outputCSRFToken(); ?>
                        <input type="hidden" name="action_type" id="actionTypeInput" value="add">
                        <input type="hidden" name="teacher_id" id="teacherIdInput" value="">
                        
                        <!-- Teacher Name Fields (3 columns) -->
                        <div class="row">
                            <!-- Last Name -->
                            <div class="col-md-6 mb-3">
                                <div class="form-floating position-relative">
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="last_name" 
                                        name="last_name"
                                        placeholder="Last name"
                                        required
                                    >
                                    <i class="bi bi-person-fill form-icon"></i>
                                    <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                </div>
                            </div>
                            
                            <!-- First Name -->
                            <div class="col-md-6 mb-3">
                                <div class="form-floating position-relative">
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="first_name" 
                                        name="first_name"
                                        placeholder="First name"
                                        required
                                    >
                                    <i class="bi bi-person-fill form-icon"></i>
                                    <label for="first_name">First Name <span class="text-danger">*</span></label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Middle Name -->
                        <div class="form-floating position-relative mb-3">
                            <input 
                                type="text" 
                                class="form-control" 
                                id="middle_name" 
                                name="middle_name"
                                placeholder="Middle name (optional)"
                            >
                            <i class="bi bi-person-fill form-icon"></i>
                            <label for="middle_name">Middle Name (Optional)</label>
                        </div>

                        <!-- Department Dropdown -->
                        <div class="form-floating position-relative mb-3">
                            <select 
                                class="form-select" 
                                id="department" 
                                name="department"
                                required
                            >
                                <option value="">-- Select Department --</option>
                                <?php foreach (ALLOWED_DEPARTMENTS as $dept): ?>
                                    <option value="<?= escapeOutput($dept) ?>">
                                        <?= escapeOutput($dept) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <i class="bi bi-building form-icon"></i>
                            <label for="department">Department <span class="text-danger">*</span></label>
                        </div>

                        <!-- Email -->
                        <div class="form-floating position-relative mb-3">
                            <input 
                                type="email" 
                                class="form-control" 
                                id="email" 
                                name="email"
                                placeholder="teacher@example.com"
                            >
                            <i class="bi bi-envelope-fill form-icon"></i>
                            <label for="email">Email Address</label>
                        </div>

                        <!-- Status -->
                        <div class="form-floating position-relative mb-4">
                            <select 
                                class="form-select" 
                                id="status" 
                                name="status"
                                required
                            >
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <i class="bi bi-check-circle-fill form-icon"></i>
                            <label for="status">Status</label>
                        </div>

                        <!-- Buttons -->
                        <div class="d-grid gap-2 d-sm-flex justify-content-sm-end">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="bi bi-x-lg"></i> Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> <span id="submitButtonText">Add</span> Teacher
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    
    <!-- Pass PHP messages to JavaScript -->
    <script>
        const pageMessages = {
            success: <?= $success_msg ? json_encode($success_msg) : 'null' ?>,
            error: <?= $error_msg ? json_encode($error_msg) : 'null' ?>
        };
    </script>
    
    <script src="/teacher-eval/assets/js/main.js"></script>
    <script src="/teacher-eval/assets/js/teachers.js"></script>
    <script src="/teacher-eval/assets/js/export-pdf.js"></script>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>
</html>

