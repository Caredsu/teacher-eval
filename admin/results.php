<?php
/**
 * Evaluation Results & Analytics
 */

require_once '../includes/helpers.php';
require_once '../config/database.php';

initializeSession();
requireLogin();

// ==================== AJAX GET EVALUATION DETAILS ====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['get_eval_details'])) {
    header('Content-Type: application/json');
    
    $eval_id = trim($_POST['get_eval_details']);
    
    if (!isValidObjectId($eval_id)) {
        echo json_encode(['success' => false, 'message' => 'Invalid evaluation ID']);
        exit;
    }
    
    try {
        $eval = $evaluations_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($eval_id)]);
        
        if (!$eval) {
            echo json_encode(['success' => false, 'message' => 'Evaluation not found']);
            exit;
        }
        
        $teacher = $teachers_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($eval['teacher_id'])]);
        $teacher_name = 'Unknown';
        if ($teacher) {
            $teacher_name = formatFullName(
                (string)($teacher['first_name'] ?? ''),
                (string)($teacher['middle_name'] ?? ''),
                (string)($teacher['last_name'] ?? '')
            );
        }
        
        $answers = [];
        if (isset($eval['answers'])) {
            $answers_array = iterator_to_array($eval['answers']);
            foreach ($answers_array as $answer) {
                $question_id = $answer['question_id'] ?? 'N/A';
                $rating = $answer['rating'] ?? 0;
                
                // Get question text from questions collection
                $question_text = $question_id;
                if (isValidObjectId($question_id)) {
                    $question = $questions_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($question_id)]);
                    if ($question && isset($question['question_text'])) {
                        $question_text = $question['question_text'];
                    }
                }
                
                $answers[] = [
                    'question' => $question_text,
                    'rating' => $rating
                ];
            }
        }
        
        // Format submitted_at with Manila timezone
        $submitted_at_formatted = 'N/A';
        if ($eval['submitted_at'] instanceof \MongoDB\BSON\UTCDateTime) {
            $dt = $eval['submitted_at']->toDateTime();
            $dt->setTimezone(new \DateTimeZone('Asia/Manila'));
            $submitted_at_formatted = $dt->format('M d, Y h:i A');
        }
        
        // Get overall feedback if available
        $feedback = $eval['feedback'] ?? 'No feedback provided';
        
        echo json_encode([
            'success' => true,
            'teacher_name' => $teacher_name,
            'submitted_at' => $submitted_at_formatted,
            'feedback' => $feedback,
            'answers' => $answers
        ]);
        exit;
    } catch (\Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        exit;
    }
}

$filter_teacher_id = getGET('teacher_id', '');
$filter_from_date = getGET('from_date', '');
$filter_to_date = getGET('to_date', '');
$filter_min_rating = getGET('min_rating', '');

// Get all teachers for filter dropdown
$teachers = $teachers_collection->find();
$teachers_list = [];
foreach ($teachers as $teacher) {
    $teachers_list[] = $teacher;
}

// Build evaluations query
$query = [];

// Teacher filter
if (!empty($filter_teacher_id) && isValidObjectId($filter_teacher_id)) {
    $query['teacher_id'] = new MongoDB\BSON\ObjectId($filter_teacher_id);
}

// Date range filters
if (!empty($filter_from_date)) {
    try {
        $from_timestamp = strtotime($filter_from_date);
        if ($from_timestamp !== false) {
            $query['submitted_at']['$gte'] = new MongoDB\BSON\UTCDateTime($from_timestamp * 1000);
        }
    } catch (Exception $e) {
        // Invalid date format, skip filter
    }
}

if (!empty($filter_to_date)) {
    try {
        // Set to end of day
        $to_timestamp = strtotime($filter_to_date . ' 23:59:59');
        if ($to_timestamp !== false) {
            $query['submitted_at']['$lte'] = new MongoDB\BSON\UTCDateTime($to_timestamp * 1000);
        }
    } catch (Exception $e) {
        // Invalid date format, skip filter
    }
}

// Minimum rating filter - requires calculating average per evaluation
$min_rating = null;
if (!empty($filter_min_rating) && is_numeric($filter_min_rating)) {
    $min_rating = (float)$filter_min_rating;
}

// Pagination settings
$per_page = 10;
$current_page = max(1, (int)getGET('page', 1));
$offset = ($current_page - 1) * $per_page;

// Get ALL evaluations first (including rating filter requirement)
$all_evaluations = $evaluations_collection->find($query, ['sort' => ['submitted_at' => -1]])->toArray();

// Apply minimum rating filter (client-side calculation)
if ($min_rating !== null) {
    $filtered_evals = [];
    foreach ($all_evaluations as $eval) {
        if (isset($eval['answers'])) {
            $answers_array = iterator_to_array($eval['answers']);
            $ratings = array_column($answers_array, 'rating');
            if (!empty($ratings)) {
                $avg = array_sum($ratings) / count($ratings);
                if ($avg >= $min_rating) {
                    $filtered_evals[] = $eval;
                }
            }
        }
    }
    $all_evaluations = $filtered_evals;
}

// Calculate pagination info
$total_evaluations = count($all_evaluations);
$total_pages = ceil($total_evaluations / $per_page);
$current_page = min($current_page, max(1, $total_pages)); // Ensure page is within bounds

// Get evaluations for current page
$evaluations = array_slice($all_evaluations, $offset, $per_page);

// Calculate statistics
$stats = [];
$question_stats = [];

if (!empty($evaluations)) {
    // Get all questions
    $questions_all = $questions_collection->find();
    $questions_map = [];
    foreach ($questions_all as $q) {
        $questions_map[objectIdToString($q['_id'])] = $q['question_text'];
    }
    
    // Process evaluations
    foreach ($evaluations as $eval) {
        if (isset($eval['answers'])) {
            foreach ($eval['answers'] as $answer) {
                $q_id = $answer['question_id'];
                $rating = $answer['rating'];
                
                if (!isset($question_stats[$q_id])) {
                    $question_stats[$q_id] = [
                        'total' => 0,
                        'sum' => 0,
                        'count' => 0,
                        'ratings' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0]
                    ];
                }
                
                $question_stats[$q_id]['total']++;
                $question_stats[$q_id]['sum'] += $rating;
                $question_stats[$q_id]['count']++;
                $question_stats[$q_id]['ratings'][$rating]++;
            }
        }
    }
    
    // Calculate averages
    foreach ($question_stats as $q_id => &$stats_item) {
        $stats_item['avg'] = $stats_item['count'] > 0 ? round($stats_item['sum'] / $stats_item['count'], 2) : 0;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Evaluation Results - Teacher Evaluation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/teacher-eval/assets/css/dark-theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
</head>
<body>
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>
    
    <!-- Main Content Wrapper -->
    <div class="main-content">
        <div class="container-fluid py-5">
        <div class="row mb-4">
            <div class="col-md-6">
                <h1 class="h2">Evaluation Results</h1>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-outline-secondary" onclick="exportResultsPDF()">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </button>
            </div>
        </div>
        
        <!-- Filter Card - Modern Design -->
        <div class="card border-0 shadow-sm mb-4" style="background: linear-gradient(135deg, #3b4a5c 0%, #2c3e50 100%); border-left: 4px solid #667eea;">
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-funnel-fill" style="font-size: 20px; color: #667eea; margin-right: 10px;"></i>
                    <h5 class="mb-0" style="color: #ffffff; font-weight: 600; font-size: 16px;">🔍 Search & Filter Results</h5>
                </div>
                
                <form method="GET" class="row g-3" id="filterForm">
                    <div class="col-lg-4 col-md-6">
                        <label for="teacher_filter" class="form-label" style="font-weight: 500; color: #e0e0e0; font-size: 13px;">
                            <i class="bi bi-person-fill" style="color: #667eea;"></i> Select Teacher
                        </label>
                        <select class="form-select" id="teacher_filter" name="teacher_id" style="border-radius: 6px; border: 1.5px solid #555; padding: 10px 12px; font-size: 14px; background: #2c3e50; color: #ffffff;">
                            <option value="" style="background: #2c3e50; color: #ffffff;">📋 All Teachers</option>
                            <?php
                            foreach ($teachers_list as $teacher) {
                                $teacher_id = objectIdToString($teacher['_id']);
                                $selected = $teacher_id === $filter_teacher_id ? 'selected' : '';
                                $full_name = formatFullName(
                                    $teacher['first_name'] ?? '',
                                    $teacher['middle_name'] ?? '',
                                    $teacher['last_name'] ?? ''
                                );
                                echo '<option value="' . escapeOutput($teacher_id) . '" ' . $selected . ' style="background: #2c3e50; color: #ffffff;">';
                                echo escapeOutput($full_name);
                                echo '</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label for="from_date" class="form-label" style="font-weight: 500; color: #e0e0e0; font-size: 13px;">
                            <i class="bi bi-calendar-event" style="color: #667eea;"></i> From Date
                        </label>
                        <input type="date" class="form-control" id="from_date" name="from_date" value="<?= escapeOutput($filter_from_date) ?>" style="border-radius: 6px; border: 1.5px solid #555; padding: 10px 12px; font-size: 14px; background: #2c3e50; color: #ffffff;">
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label for="to_date" class="form-label" style="font-weight: 500; color: #e0e0e0; font-size: 13px;">
                            <i class="bi bi-calendar-check" style="color: #667eea;"></i> To Date
                        </label>
                        <input type="date" class="form-control" id="to_date" name="to_date" value="<?= escapeOutput($filter_to_date) ?>" style="border-radius: 6px; border: 1.5px solid #555; padding: 10px 12px; font-size: 14px; background: #2c3e50; color: #ffffff;">
                    </div>

                    <div class="col-lg-2 col-md-6">
                        <label for="min_rating" class="form-label" style="font-weight: 500; color: #e0e0e0; font-size: 13px;">
                            <i class="bi bi-star-fill" style="color: #ffc107;"></i> Min Rating
                        </label>
                        <select class="form-select" id="min_rating" name="min_rating" style="border-radius: 6px; border: 1.5px solid #555; padding: 10px 12px; font-size: 14px; background: #2c3e50; color: #ffffff;">
                            <option value="" style="background: #2c3e50; color: #ffffff;">All Ratings</option>
                            <option value="1" <?= $filter_min_rating === '1' ? 'selected' : '' ?> style="background: #2c3e50; color: #ffffff;">⭐ 1+ Stars</option>
                            <option value="2" <?= $filter_min_rating === '2' ? 'selected' : '' ?> style="background: #2c3e50; color: #ffffff;">⭐⭐ 2+ Stars</option>
                            <option value="3" <?= $filter_min_rating === '3' ? 'selected' : '' ?> style="background: #2c3e50; color: #ffffff;">⭐⭐⭐ 3+ Stars</option>
                            <option value="4" <?= $filter_min_rating === '4' ? 'selected' : '' ?> style="background: #2c3e50; color: #ffffff;">⭐⭐⭐⭐ 4+ Stars</option>
                            <option value="5" <?= $filter_min_rating === '5' ? 'selected' : '' ?> style="background: #2c3e50; color: #ffffff;">⭐⭐⭐⭐⭐ 5 Stars</option>
                        </select>
                    </div>

                    <div class="col-lg-2 col-md-6 d-flex align-items-end gap-2">
                        <button type="submit" class="btn w-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 6px; font-weight: 500; padding: 10px 12px;">
                            <i class="bi bi-search"></i> Search
                        </button>
                    </div>

                    <div class="col-12">
                        <?php if ($filter_teacher_id || $filter_from_date || $filter_to_date || $filter_min_rating): ?>
                            <a href="/teacher-eval/admin/results.php" class="btn btn-sm" style="color: #ffffff; background: #3b4a5c; border: 1px solid #555; border-radius: 6px;">
                                <i class="bi bi-arrow-clockwise"></i> Clear Filters
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>

                <!-- Filter Summary -->
                <div style="margin-top: 15px; padding: 12px 16px; background: #2c3e50; border-radius: 6px; border-left: 3px solid #667eea;" id="filterSummary">
                    <?php
                    $active_filters = [];
                    if (!empty($filter_teacher_id)) {
                        $teacher = $teachers_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($filter_teacher_id)]);
                        if ($teacher) {
                            $active_filters[] = '<strong style="color: #ffffff;">👨‍🏫 ' . formatFullName((string)($teacher['first_name'] ?? ''), (string)($teacher['middle_name'] ?? ''), (string)($teacher['last_name'] ?? '')) . '</strong>';
                        }
                    }
                    if (!empty($filter_from_date)) {
                        $active_filters[] = '<strong style="color: #e0e0e0;">📅 Start:</strong> <span style="color: #ffffff;">' . $filter_from_date . '</span>';
                    }
                    if (!empty($filter_to_date)) {
                        $active_filters[] = '<strong style="color: #e0e0e0;">📅 End:</strong> <span style="color: #ffffff;">' . $filter_to_date . '</span>';
                    }
                    if (!empty($filter_min_rating)) {
                        $active_filters[] = '<strong style="color: #e0e0e0;">⭐ Min:</strong> <span style="color: #ffffff;">' . $filter_min_rating . '+ stars</span>';
                    }
                    
                    if (!empty($active_filters)): ?>
                        <small style="color: #e0e0e0; font-size: 13px;">
                            <strong style="color: #667eea;">🔖 Active Filters:</strong> <?= implode(' • ', $active_filters) ?> 
                            <a href="/teacher-eval/admin/results.php" class="text-decoration-none ms-2" style="color: #667eea; font-weight: 500;">(Clear all)</a>
                        </small>
                    <?php endif; ?>
                </div>
        
        <!-- Results Summary -->
        <div class="alert d-flex align-items-center justify-content-between flex-wrap" style="background: linear-gradient(135deg, #e8f4f8 0%, #e0f0ff 100%); border-radius: 8px; border-left: 4px solid #3498db; color: #1a1a1a; border: none; margin-bottom: 25px; padding: 16px 20px;" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-graph-up me-3" style="font-size: 18px; color: #3498db;"></i>
                <div>
                    <strong style="font-size: 15px;">📊 <?= $total_evaluations ?></strong> evaluation(s) found
                    <?php if ($total_evaluations > 0): ?>
                        • Showing <strong><?= count($evaluations) > 0 ? $offset + 1 : 0 ?> - <?= min($offset + $per_page, $total_evaluations) ?></strong>
                    <?php endif; ?>
                </div>
            </div>
            <?php if ($total_pages > 1): ?>
            <div style="color: #666; font-size: 13px;">
                Page <strong><?= $current_page ?></strong> of <strong><?= $total_pages ?></strong>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Statistics Cards - Simple Design -->
        <?php if (!empty($evaluations)): ?>
        <div class="row g-3 mb-4">
            <!-- Total Responses Card -->
            <div class="col-lg-3 col-md-6">
                <div style="background: #2c3e50; border: 1px solid #3d5066; border-radius: 10px; padding: 20px; border-left: 4px solid #667eea; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <p style="margin: 0 0 8px 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">📝 Total Responses</p>
                        <h2 style="margin: 0; font-size: 28px; font-weight: 700; color: #ffffff;"><?= count($evaluations) ?></h2>
                    </div>
                    <div style="font-size: 32px; opacity: 0.3;">📊</div>
                </div>
            </div>
            
            <!-- Average Rating Card -->
            <div class="col-lg-3 col-md-6">
                <div style="background: #2c3e50; border: 1px solid #3d5066; border-radius: 10px; padding: 20px; border-left: 4px solid #27ae60; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <p style="margin: 0 0 8px 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">⭐ Average Rating</p>
                        <h2 style="margin: 0; font-size: 28px; font-weight: 700; color: #ffffff;">
                            <?php
                            $total_sum = 0;
                            $total_count = 0;
                            foreach ($question_stats as $q_stat) {
                                $total_sum += $q_stat['sum'];
                                $total_count += $q_stat['count'];
                            }
                            echo $total_count > 0 ? round($total_sum / $total_count, 2) : 'N/A';
                            ?>
                            <span style="font-size: 18px; color: #bbb;"> / 5</span>
                        </h2>
                    </div>
                    <div style="font-size: 32px; opacity: 0.3;">🌟</div>
                </div>
            </div>
            
            <!-- Questions Card -->
            <div class="col-lg-3 col-md-6">
                <div style="background: #2c3e50; border: 1px solid #3d5066; border-radius: 10px; padding: 20px; border-left: 4px solid #f39c12; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <p style="margin: 0 0 8px 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">❓ Questions</p>
                        <h2 style="margin: 0; font-size: 28px; font-weight: 700; color: #ffffff;"><?= count($question_stats) ?></h2>
                    </div>
                    <div style="font-size: 32px; opacity: 0.3;">📋</div>
                </div>
            </div>
            
            <!-- Response Rate Card -->
            <div class="col-lg-3 col-md-6">
                <div style="background: #2c3e50; border: 1px solid #3d5066; border-radius: 10px; padding: 20px; border-left: 4px solid #3498db; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <p style="margin: 0 0 8px 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">✅ Response Rate</p>
                        <h2 style="margin: 0; font-size: 28px; font-weight: 700; color: #ffffff;">100<span style="font-size: 18px; color: #bbb;">%</span></h2>
                    </div>
                    <div style="font-size: 32px; opacity: 0.3;">📈</div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Question-wise Analysis -->
        <div class="row">
            <?php
            foreach ($question_stats as $q_id => $stats) {
                $question_text = isset($questions_map[$q_id]) ? $questions_map[$q_id] : 'Unknown Question';
                ?>
                <div class="col-md-6 mb-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-light">
                            <div class="row">
                                <div class="col-md-8">
                                    <h6 class="mb-0"><?= escapeOutput($question_text) ?></h6>
                                </div>
                                <div class="col-md-4 text-end">
                                    <span class="badge bg-success">Average: <?= $stats['avg'] ?>/5</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="chart_<?= escapeOutput($q_id) ?>"></canvas>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        
        <!-- Detailed Evaluations Table -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Detailed Evaluations</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Teacher</th>
                                        <th>Date</th>
                                        <th>Average Rating</th>
                                        <th>Responses</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($evaluations as $eval) {
                                        if (!isValidObjectId($eval['teacher_id'])) continue;
                                        
                                        $teacher = $teachers_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($eval['teacher_id'])]);
                                        $avg_rating = 0;
                                        
                                        if (isset($eval['answers'])) {
                                            // Convert MongoDB BSONArray to PHP array
                                            $answers_array = iterator_to_array($eval['answers']);
                                            $ratings = [];
                                            foreach ($answers_array as $answer) {
                                                if (isset($answer['rating'])) {
                                                    $ratings[] = (float)$answer['rating'];
                                                }
                                            }
                                            $avg_rating = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 1) : 0;
                                        }
                                        
                                        $teacher_name = 'Unknown';
                                        if ($teacher) {
                                            $teacher_name = formatFullName(
                                                (string)($teacher['first_name'] ?? ''),
                                                (string)($teacher['middle_name'] ?? ''),
                                                (string)($teacher['last_name'] ?? '')
                                            );
                                        }
                                        
                                        $eval_id = objectIdToString($eval['_id']);
                                        echo '
                                        <tr>
                                            <td class="fw-500">' . escapeOutput($teacher_name) . '</td>
                                            <td>' . formatDateTime($eval['submitted_at'] ?? '') . '</td>
                                            <td><span class="badge bg-success">' . $avg_rating . '/5</span></td>
                                            <td>' . (isset($eval['answers']) ? count($eval['answers']) : 0) . ' answers</td>
                                            <td><button class="btn btn-sm btn-outline-primary btn-view-eval" data-eval-id="' . escapeOutput($eval_id) . '" title="View Details"><i class="bi bi-eye"></i> View</button></td>
                                        </tr>
                                        ';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>                        
                        <!-- Pagination Controls -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center mb-0">
                                <!-- Previous Button -->
                                <li class="page-item <?= $current_page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= max(1, $current_page - 1) ?>&teacher_id=<?= escapeOutput($filter_teacher_id) ?>&from_date=<?= escapeOutput($filter_from_date) ?>&to_date=<?= escapeOutput($filter_to_date) ?>&min_rating=<?= escapeOutput($filter_min_rating) ?>">
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
                                        <a class="page-link" href="?page=1&teacher_id=<?= escapeOutput($filter_teacher_id) ?>&from_date=<?= escapeOutput($filter_from_date) ?>&to_date=<?= escapeOutput($filter_to_date) ?>&min_rating=<?= escapeOutput($filter_min_rating) ?>">1</a>
                                    </li>
                                    <?php if ($start_page > 2): ?>
                                    <li class="page-item disabled">
                                        <span class="page-link">...</span>
                                    </li>
                                    <?php endif; ?>
                                <?php endif; ?>
                                
                                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <li class="page-item <?= $i == $current_page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?>&teacher_id=<?= escapeOutput($filter_teacher_id) ?>&from_date=<?= escapeOutput($filter_from_date) ?>&to_date=<?= escapeOutput($filter_to_date) ?>&min_rating=<?= escapeOutput($filter_min_rating) ?>">
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
                                        <a class="page-link" href="?page=<?= $total_pages ?>&teacher_id=<?= escapeOutput($filter_teacher_id) ?>&from_date=<?= escapeOutput($filter_from_date) ?>&to_date=<?= escapeOutput($filter_to_date) ?>&min_rating=<?= escapeOutput($filter_min_rating) ?>"><?= $total_pages ?></a>
                                    </li>
                                <?php endif; ?>
                                
                                <!-- Next Button -->
                                <li class="page-item <?= $current_page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= min($total_pages, $current_page + 1) ?>&teacher_id=<?= escapeOutput($filter_teacher_id) ?>&from_date=<?= escapeOutput($filter_from_date) ?>&to_date=<?= escapeOutput($filter_to_date) ?>&min_rating=<?= escapeOutput($filter_min_rating) ?>">
                                        Next <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (empty($evaluations)): ?>
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-info-circle fs-2"></i>
            <p class="mt-3">No evaluations found yet.</p>
        </div>
        <?php endif; ?>
    </div>
    </div>  <!-- Close main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="/teacher-eval/assets/js/main.js"></script>
    <script src="/teacher-eval/assets/js/confirmation.js"></script>
    <script src="/teacher-eval/assets/js/export-pdf.js"></script>
    <script>
        // Generate charts for each question
        <?php foreach ($question_stats as $q_id => $stats): ?>
        const ctx_<?= str_replace('-', '_', $q_id) ?> = document.getElementById('chart_<?= escapeOutput($q_id) ?>').getContext('2d');
        new Chart(ctx_<?= str_replace('-', '_', $q_id) ?>, {
            type: 'bar',
            data: {
                labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                datasets: [{
                    label: 'Number of Responses',
                    data: [
                        <?= $stats['ratings'][1] ?>,
                        <?= $stats['ratings'][2] ?>,
                        <?= $stats['ratings'][3] ?>,
                        <?= $stats['ratings'][4] ?>,
                        <?= $stats['ratings'][5] ?>
                    ],
                    backgroundColor: ['#dc3545', '#fd7e14', '#ffc107', '#28a745', '#198754']
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
        <?php endforeach; ?>
    </script>
    
    <!-- Evaluation Details Script -->
    <script>
        // Get CSRF token
        function getCSRFToken() {
            return document.querySelector('input[name="csrf_token"]')?.value || '';
        }
        
        // Setup view buttons
        document.querySelectorAll('.btn-view-eval').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const evalId = this.getAttribute('data-eval-id');
                viewEvaluationDetails(evalId);
            });
        });
        
        // Fetch and display evaluation details
        function viewEvaluationDetails(evalId) {
            const formData = new FormData();
            formData.append('get_eval_details', evalId);
            
            fetch('/teacher-eval/admin/results.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayEvalDetails(data);
                    const modal = new bootstrap.Modal(document.getElementById('evalDetailsModal'));
                    modal.show();
                } else {
                    Toast.error(data.message || 'Failed to load evaluation details');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.error('Error loading evaluation details');
            });
        }
        
        // Display evaluation details in modal
        function displayEvalDetails(data) {
            let html = '<div class="evaluation-details">';
            html += '<div class="mb-3" style="color: #ffffff;"><strong style="color: #e0e0e0;">Teacher:</strong> ' + escapeHtml(data.teacher_name) + '</div>';
            html += '<div class="mb-3" style="color: #ffffff;"><strong style="color: #e0e0e0;">Submitted:</strong> ' + escapeHtml(data.submitted_at) + '</div>';
            html += '<hr style="border-color: #555;">';
            html += '<h6 style="color: #e0e0e0;">Ratings by Question:</h6>';
            html += '<div class="table-responsive">';
            html += '<table class="table table-sm" style="color: #ffffff;">';
            html += '<thead style="background: #2c3e50; color: #e0e0e0;"><tr><th>Question</th><th>Rating</th></tr></thead>';
            html += '<tbody>';
            
            if (data.answers && data.answers.length > 0) {
                data.answers.forEach((answer, index) => {
                    const rowBg = index % 2 === 0 ? '#1a252f' : '#0f1419';
                    html += '<tr style="background: ' + rowBg + '; color: #ffffff;">';
                    html += '<td style="color: #ffffff;">' + escapeHtml(answer.question) + '</td>';
                    html += '<td><span class="badge" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">' + answer.rating + '/5</span></td>';
                    html += '</tr>';
                });
            } else {
                html += '<tr><td colspan="2" class="text-center" style="color: #999;">No ratings found</td></tr>';
            }
            
            html += '</tbody></table></div>';
            html += '<hr style="border-color: #555;">';
            html += '<h6 style="color: #e0e0e0;">Overall Feedback:</h6>';
            html += '<div style="background: #1a252f; color: #ffffff; padding: 15px; border-radius: 6px; border-left: 3px solid #667eea; min-height: 80px; word-wrap: break-word;">' + escapeHtml(data.feedback) + '</div>';
            html += '</div>';
            
            const container = document.getElementById('evalDetailsContent');
            container.className = '';
            container.innerHTML = html;
        }
        
        // Simple HTML escape function
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
    
    <!-- Evaluation Details Modal -->
    <div class="modal fade" id="evalDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background: #2c3e50; color: #ffffff; border: none;">
                <div class="modal-header" style="background: #1a252f; border-bottom: 1px solid #555;">
                    <h5 class="modal-title" style="color: #ffffff;">Evaluation Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" style="background: #2c3e50;">
                    <div id="evalDetailsContent" class="spinner-border text-light" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>
</html>

