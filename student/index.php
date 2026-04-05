<?php
/**
 * Student Evaluation Form - Anonymous
 * No login required, mobile-friendly
 */

require_once '../includes/helpers.php';
require_once '../config/database.php';

initializeSession();

// Track evaluated teachers in this session (allow multiple teachers, 1 per teacher)
if (!isset($_SESSION['evaluated_teachers'])) {
    $_SESSION['evaluated_teachers'] = [];
}

$success_msg = getSuccessMessage();
$error_msg = getErrorMessage();
$last_success = isset($_SESSION['last_success']) ? $_SESSION['last_success'] : null;

// Clear the success message after retrieving it (so it only shows once)
if ($last_success) {
    unset($_SESSION['last_success']);
}

// Handle AJAX form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_evaluation'])) {
    header('Content-Type: application/json');
    
    try {
        // Verify CSRF token
        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            jsonResponse(false, 'Security token invalid.', []);
        }
        
        $teacher_id = sanitizeInput($_POST['teacher_id'] ?? '');
        $subject = sanitizeInput($_POST['subject'] ?? '');
        $answers = $_POST['answers'] ?? [];
        
        // Validate inputs
        if (empty($teacher_id) || !isValidObjectId($teacher_id)) {
            jsonResponse(false, 'Please select a teacher.', []);
        }
        
        // Verify teacher exists and is active
        $teacher = $teachers_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($teacher_id)]);
        if (!$teacher || ($teacher['status'] ?? 'active') !== 'active') {
            jsonResponse(false, 'This teacher is not available for evaluation.', []);
        }
        
        if (empty($subject)) {
            jsonResponse(false, 'Please select a subject/class.', []);
        }
        
        if (empty($answers) || count($answers) === 0) {
            jsonResponse(false, 'Please answer all questions.', []);
        }
        
        // Check if this teacher was already evaluated in this session
        if (in_array($teacher_id, $_SESSION['evaluated_teachers'])) {
            jsonResponse(false, 'You have already evaluated this teacher. You can evaluate another teacher if available.', []);
        }
        
        // Check if already evaluated in database (IP + teacher combo)
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $existing = $evaluations_collection->findOne([
            'teacher_id' => $teacher_id,
            'ip_address' => $ip_address
        ]);
        
        if ($existing) {
            jsonResponse(false, 'This teacher has already been evaluated from your device.', []);
        }
        
        // Prepare answers
        $answers_array = [];
        foreach ($answers as $question_id => $rating) {
            if (!isValidObjectId($question_id) || !is_numeric($rating)) {
                continue;
            }
            $rating = (int)$rating;
            if ($rating >= 1 && $rating <= 5) {
                $answers_array[] = [
                    'question_id' => $question_id,
                    'rating' => $rating
                ];
            }
        }
        
        if (count($answers_array) === 0) {
            jsonResponse(false, 'Please provide valid ratings.', []);
        }
        
        // Save evaluation to MongoDB
        $result = $evaluations_collection->insertOne([
            'teacher_id' => $teacher_id,
            'subject' => $subject,
            'answers' => $answers_array,
            'created_at' => new MongoDB\BSON\UTCDateTime(),
            'ip_address' => $ip_address,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        // Format teacher name for success message (using previously fetched $teacher)
        $teacher_name = formatFullName(
            $teacher['first_name'] ?? '',
            $teacher['middle_name'] ?? '',
            $teacher['last_name'] ?? ''
        );
        
        // Track teacher as evaluated in this session
        $_SESSION['evaluated_teachers'][] = $teacher_id;
        $_SESSION['last_success'] = [
            'teacher_name' => $teacher_name,
            'subject' => $subject
        ];
        
        logActivity('EVALUATION_SUBMITTED', 'Student evaluation submitted for teacher: ' . $teacher_name);
        
        jsonResponse(true, 'Thank you! Your evaluation has been submitted successfully.', []);
        
    } catch (\Exception $e) {
        jsonResponse(false, 'Error: ' . $e->getMessage(), []);
    }
}

// Get teachers and questions for the form
$eval_settings = $settings_collection->findOne(['_id' => 'evaluation_settings']);
$evaluation_status = $eval_settings['status'] ?? 'on';

$teachers = $teachers_collection->find(['status' => 'active'], ['sort' => ['last_name' => 1, 'first_name' => 1]])->toArray();
$questions = $questions_collection->find(['status' => 'active'], ['sort' => ['question_order' => 1, 'created_at' => 1]])->toArray();

// Check if no data or evaluations are closed
$has_data = count($teachers) > 0 && count($questions) > 0 && $evaluation_status === 'on';
$evaluations_closed = $evaluation_status === 'off';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Evaluation - Quick Feedback</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/teacher-eval/assets/css/dark-theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        :root {
            --bg-gradient-1: #667eea;
            --bg-gradient-2: #764ba2;
        }

        body {
            background: linear-gradient(135deg, var(--bg-gradient-1) 0%, var(--bg-gradient-2) 100%);
            min-height: 100vh;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body.dark-mode {
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%);
        }
        
        .eval-container {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .eval-header {
            background: white;
            border-radius: 12px 12px 0 0;
            padding: 30px 20px;
            text-align: center;
            border-bottom: 3px solid #667eea;
        }

        body.dark-mode .eval-header {
            background: #2d2d2d;
            border-bottom: 3px solid #667eea;
        }
        
        .eval-header h1 {
            color: #333;
            font-weight: 700;
            margin-bottom: 10px;
            font-size: 24px;
        }

        body.dark-mode .eval-header h1 {
            color: #e0e0e0;
        }
        
        .eval-header p {
            color: #666;
            font-size: 14px;
            margin: 0;
        }

        body.dark-mode .eval-header p {
            color: #a0a0a0;
        }
        
        .eval-form {
            background: white;
            border-radius: 0 0 12px 12px;
            padding: 25px 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        }

        body.dark-mode .eval-form {
            background: #2d2d2d;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.5);
        }
        
        .form-section {
            margin-bottom: 25px;
        }
        
        .form-section:last-child {
            margin-bottom: 0;
        }
        
        .form-section label {
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
            display: block;
            font-size: 14px;
        }

        body.dark-mode .form-section label {
            color: #e0e0e0;
        }
        
        .form-section select {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 12px;
            width: 100%;
            font-size: 14px;
            transition: all 0.3s;
            background-color: white;
            color: #333;
        }

        body.dark-mode .form-section select {
            background-color: #1a1a1a;
            color: #e0e0e0;
            border-color: #444;
        }
        
        .form-section select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .question-group {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
            border-left: 4px solid #667eea;
        }

        body.dark-mode .question-group {
            background: rgba(102, 126, 234, 0.1);
            border-left-color: #667eea;
        }
        
        .question-text {
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
            font-size: 14px;
        }

        body.dark-mode .question-text {
            color: #e0e0e0;
        }
        
        .rating-options {
            display: flex;
            justify-content: space-between;
            gap: 8px;
        }
        
        .rating-option {
            flex: 1;
        }
        
        .rating-option input[type="radio"] {
            display: none;
        }
        
        .rating-option label {
            display: block;
            background: white;
            border: 2px solid #ddd;
            border-radius: 6px;
            padding: 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
            font-size: 12px;
            color: #666;
            margin: 0;
        }

        body.dark-mode .rating-option label {
            background: #1a1a1a;
            border-color: #444;
            color: #a0a0a0;
        }
        
        .rating-option input[type="radio"]:checked + label {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        .rating-option label:hover {
            border-color: #667eea;
        }
        
        .rating-scale {
            display: flex;
            justify-content: space-between;
            margin-top: 8px;
            font-size: 11px;
            color: #999;
        }

        body.dark-mode .rating-scale {
            color: #666;
        }
        
        .submit-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .submit-btn:hover {
            transform: translateY(-2px);
            color: white;
        }
        
        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .spinner-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        
        .spinner-overlay.active {
            display: flex;
        }
        
        .already-submitted {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            color: #664d03;
        }

        body.dark-mode .already-submitted {
            background: rgba(255, 193, 7, 0.2);
            border-color: #667eea;
            color: #ffc107;
        }
        
        .success-message {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            color: #155724;
            text-align: center;
        }

        body.dark-mode .success-message {
            background: rgba(40, 167, 69, 0.2);
            border-color: #28a745;
            color: #51cf66;
        }

        .success-message h5 {
            color: #155724;
            margin-bottom: 10px;
        }

        body.dark-mode .success-message h5 {
            color: #51cf66;
        }

        .success-message p {
            margin: 10px 0 0 0;
            font-size: 14px;
        }
        
        .alert-warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            color: #664d03;
        }

        body.dark-mode .alert-warning {
            background: rgba(255, 193, 7, 0.15);
            border-color: #ffc107;
            color: #ffc107;
        }
        
        @media (max-width: 576px) {
            .eval-header {
                padding: 20px 15px;
            }
            
            .eval-form {
                padding: 15px;
            }
            
            .eval-header h1 {
                font-size: 20px;
            }
            
            .rating-options {
                gap: 4px;
            }
        }
    </style>
</head>
<body>
    <div class="eval-container">
        <!-- Header -->
        <div class="eval-header">
            <h1><i class="bi bi-star"></i> Teacher Evaluation</h1>
            <p>We value your honest feedback (Anonymous)</p>
        </div>
        
        <!-- Form -->
        <div class="eval-form">
            <?php if ($evaluations_closed): ?>
                <!-- Evaluations Closed Message -->
                <div class="alert alert-warning text-center" style="border-radius: 8px; padding: 25px;">
                    <i class="bi bi-exclamation-triangle" style="font-size: 32px;"></i>
                    <h5 class="mt-3 mb-2">Evaluations Temporarily Closed</h5>
                    <p class="mb-0">Evaluation sessions are currently closed. Please check back later!</p>
                </div>
            <?php elseif (!$has_data): ?>
                <!-- No Data Message -->
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle"></i> No evaluations available at this time. Please try again later.
                </div>
            <?php elseif ($last_success): ?>
                <!-- Success & Continue Prompt -->
                <div style="text-align: center; margin-bottom: 25px;">
                    <div class="success-message">
                        <i class="bi bi-check-circle" style="font-size: 32px; color: #28a745;"></i>
                        <h5 class="mt-3">✓ Evaluation Submitted!</h5>
                        <p>Thank you for evaluating <strong><?= escapeOutput($last_success['teacher_name']) ?></strong></p>
                        <p><small style="color: #666;">Subject: <?= escapeOutput($last_success['subject']) ?></small></p>
                    </div>
                    
                    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
                        <button type="button" class="btn btn-primary" onclick="location.reload();" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 10px 25px;">
                            <i class="bi bi-plus-circle"></i> Evaluate Another Teacher
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="window.close();" style="padding: 10px 25px;">
                            Done <i class="bi bi-check"></i>
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <!-- Evaluation Form -->
                <form id="evaluationForm" method="POST">
                    <?php outputCSRFToken(); ?>
                    <input type="hidden" name="submit_evaluation" value="1">
                    
                    <!-- Teacher Selection -->
                    <div class="form-section">
                        <label for="teacher_id">
                            Select Your Teacher *
                            <?php if (!empty($_SESSION['evaluated_teachers'])): ?>
                                <small style="color: #666; font-weight: normal;">
                                    (<?= count($_SESSION['evaluated_teachers']) ?> evaluated)
                                </small>
                            <?php endif; ?>
                        </label>
                        <select id="teacher_id" name="teacher_id" required>
                            <option value="">-- Choose Teacher --</option>
                            <?php
                            $unevaluated_count = 0;
                            foreach ($teachers as $teacher) {
                                $teacher_id = objectIdToString($teacher['_id']);
                                $teacher_name = formatFullName(
                                    $teacher['first_name'] ?? '',
                                    $teacher['middle_name'] ?? '',
                                    $teacher['last_name'] ?? ''
                                );
                                $teacher_name = escapeOutput($teacher_name);
                                $is_evaluated = in_array($teacher_id, $_SESSION['evaluated_teachers']);
                                
                                if ($is_evaluated) {
                                    echo "<option value=\"$teacher_id\" disabled style=\"color: #999;\">✓ $teacher_name (Already Evaluated)</option>";
                                } else {
                                    echo "<option value=\"$teacher_id\">$teacher_name</option>";
                                    $unevaluated_count++;
                                }
                            }
                            ?>
                        </select>
                        <?php if ($unevaluated_count === 0): ?>
                            <div style="background: #d4edda; border: 1px solid #c3e6cb; border-radius: 6px; padding: 12px; margin-top: 10px; color: #155724; font-size: 14px;">
                                <i class="bi bi-check-circle"></i> You have evaluated all available teachers. Thank you!
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Subject/Class Selection -->
                    <div class="form-section">
                        <label for="subject">Subject / Class *</label>
                        <input 
                            type="text" 
                            id="subject" 
                            name="subject" 
                            placeholder="e.g., Physics, Chapter 3"
                            required
                            style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 10px 12px; width: 100%; font-size: 14px;"
                        >
                    </div>
                    
                    <!-- Questions Section -->
                    <div class="form-section">
                        <label style="margin-bottom: 16px;">Evaluation Questions *</label>
                        
                        <?php foreach ($questions as $index => $question): ?>
                            <?php $question_id = objectIdToString($question['_id']); ?>
                            <div class="question-group">
                                <div class="question-text">
                                    <?= $index + 1 ?>. <?= escapeOutput($question['question_text']) ?>
                                </div>
                                
                                <div class="rating-options">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="rating-option">
                                        <input 
                                            type="radio" 
                                            id="q_<?= escapeOutput($question_id) ?>_<?= $i ?>" 
                                            name="answers[<?= escapeOutput($question_id) ?>]" 
                                            value="<?= $i ?>"
                                            required
                                        >
                                        <label for="q_<?= escapeOutput($question_id) ?>_<?= $i ?>">
                                            <?php if ($i === 1): ?>😞<?php elseif ($i === 2): ?>😐<?php elseif ($i === 3): ?>😊<?php elseif ($i === 4): ?>😄<?php else: ?>😍<?php endif; ?>
                                            <br><small><?= $i ?></small>
                                        </label>
                                    </div>
                                    <?php endfor; ?>
                                </div>
                                
                                <div class="rating-scale">
                                    <span>Poor</span>
                                    <span>Excellent</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Submit Button -->
                    <?php 
                    $all_evaluated = ($unevaluated_count === 0);
                    ?>
                    <button type="submit" class="submit-btn" <?= $all_evaluated ? 'disabled onclick="return false;"' : '' ?>>
                        <i class="bi bi-send"></i> Submit Evaluation
                    </button>
                </form>
            <?php endif; ?>
        </div>
        
        <!-- Footer Note -->
        <div style="text-align: center; color: white; margin-top: 20px; font-size: 12px;">
            <p>Your response is completely anonymous and secure 🔒</p>
        </div>
    </div>

    <!-- Loading Spinner -->
    <div class="spinner-overlay" id="spinnerOverlay">
        <div class="spinner-border text-white" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
    
    <script>
        const form = document.getElementById('evaluationForm');
        
        if (form) {
            form.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                // Show spinner
                document.getElementById('spinnerOverlay').classList.add('active');
                
                const formData = new FormData(form);
                
                try {
                    const response = await fetch(window.location.href, {
                        method: 'POST',
                        body: formData
                    });
                    
                    const data = await response.json();
                    
                    document.getElementById('spinnerOverlay').classList.remove('active');
                    
                    if (data.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'Thank You!',
                            text: data.message,
                            confirmButtonColor: '#667eea',
                            confirmButtonText: 'Evaluate Another Teacher'
                        });
                        
                        // Reload page to show success message and clear form
                        location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message,
                            confirmButtonColor: '#667eea'
                        });
                    }
                } catch (error) {
                    document.getElementById('spinnerOverlay').classList.remove('active');
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An unexpected error occurred. Please try again.',
                        confirmButtonColor: '#667eea'
                    });
                }
            });
        }
    </script>
</body>
</html>

