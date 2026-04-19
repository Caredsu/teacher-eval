<?php
/**
 * Admin Dashboard
 */

require_once '../includes/helpers.php';
require_once '../config/database.php';

// Add HTTP cache headers for static dashboard data (3 minutes)
if (!isset($_GET['get_notifications']) && !isset($_GET['get_notif_count']) && !isset($_GET['clear_notifications']) && !isset($_GET['check_new'])) {
    header('Cache-Control: public, max-age=180'); // 3 minutes
    header('Pragma: cache');
}

// Enable gzip compression if available
if (!ob_get_contents() && extension_loaded('zlib')) {
    ob_start('ob_gzhandler');
}

initializeSession();
requireLogin();

// Handle AJAX notification count request
if (isset($_GET['get_notif_count'])) {
    header('Content-Type: application/json');
    try {
        // Use estimated count for speed
        $total_count = $evaluations_collection->estimatedDocumentCount();
        echo json_encode(['count' => $total_count]);
    } catch (\Exception $e) {
        echo json_encode(['count' => 0]);
    }
    exit;
}

// Handle AJAX clear notifications request
if (isset($_GET['clear_notifications'])) {
    header('Content-Type: application/json');
    try {
        // Get current estimated count and save it as the baseline
        $current_count = $evaluations_collection->estimatedDocumentCount();
        $_SESSION['notifications_cleared_at'] = (string)$current_count;
        echo json_encode(['success' => true]);
    } catch (\Exception $e) {
        echo json_encode(['success' => false]);
    }
    exit;
}

// Handle AJAX get notifications request
if (isset($_GET['get_notifications'])) {
    header('Content-Type: application/json');
    try {
        $total_count = $evaluations_collection->estimatedDocumentCount();
        
        // Get cleared baseline from session
        $cleared_baseline = isset($_SESSION['notifications_cleared_at']) 
            ? (int)$_SESSION['notifications_cleared_at'] 
            : 0;
        
        // Calculate "new" notifications since last clear (for badge)
        $new_count = max(0, $total_count - $cleared_baseline);
        
        // But ALWAYS show last 5 evaluations in the list (with field projection)
        $recent_evals = $evaluations_collection->find(
            [],
            [
                'projection' => ['teacher_id' => 1, 'submitted_at' => 1],
                'sort' => ['submitted_at' => -1],
                'limit' => 5
            ]
        );
        
        // Collect teacher IDs we need
        $needed_teacher_ids = [];
        $notifications = [];
        foreach ($recent_evals as $eval) {
            $teacher_id = $eval['teacher_id'] ?? null;
            if ($teacher_id) {
                $needed_teacher_ids[(string)$teacher_id] = true;
            }
        }
        
        // Fetch all needed teachers in one query with field projection
        $teachers_by_id = [];
        if (count($needed_teacher_ids) > 0) {
            $teacher_ids_to_fetch = array_map(function($id) {
                return ctype_xdigit($id) && strlen($id) === 24 
                    ? new MongoDB\BSON\ObjectId($id) 
                    : $id;
            }, array_keys($needed_teacher_ids));
            
            $found_teachers = $teachers_collection->find(
                ['_id' => ['$in' => $teacher_ids_to_fetch]],
                ['projection' => ['name' => 1, 'first_name' => 1, 'last_name' => 1]]
            );
            foreach ($found_teachers as $teacher) {
                $teachers_by_id[(string)$teacher['_id']] = $teacher;
            }
        }
        
        // Re-query recent evals for display (since we iterated through the cursor)
        $recent_evals = $evaluations_collection->find(
            [],
            [
                'projection' => ['teacher_id' => 1, 'submitted_at' => 1],
                'sort' => ['submitted_at' => -1],
                'limit' => 5
            ]
        );
        
        foreach ($recent_evals as $eval) {
            $teacher_name = 'Anonymous';
            $teacher_id = $eval['teacher_id'] ?? null;
            
            // Look up teacher from cache instead of querying
            if ($teacher_id && isset($teachers_by_id[(string)$teacher_id])) {
                $teacher = $teachers_by_id[(string)$teacher_id];
                $teacher_name = $teacher['name'] ?? 'Anonymous';
            }
            
            // Format time
            $time_text = 'just now';
            if (isset($eval['submitted_at'])) {
                try {
                    $date_obj = $eval['submitted_at'];
                    if ($date_obj instanceof MongoDB\BSON\UTCDateTime) {
                        $timestamp = $date_obj->toDateTime();
                    } elseif ($date_obj instanceof DateTime) {
                        $timestamp = $date_obj;
                    } else {
                        $timestamp = new DateTime();
                    }
                    
                    $now = new DateTime();
                    $diff = $now->getTimestamp() - $timestamp->getTimestamp();
                    
                    if ($diff < 60) {
                        $time_text = 'just now';
                    } elseif ($diff < 3600) {
                        $mins = floor($diff / 60);
                        $time_text = $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
                    } elseif ($diff < 86400) {
                        $hours = floor($diff / 3600);
                        $time_text = $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
                    } else {
                        $days = floor($diff / 86400);
                        $time_text = $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
                    }
                } catch (\Exception $e) {
                    $time_text = 'recently';
                }
            }
            
            $notifications[] = [
                'title' => $teacher_name . ' was evaluated',
                'time' => $time_text
            ];
        }
        
        echo json_encode([
            'count' => $new_count,
            'total' => $total_count,
            'notifications' => $notifications
        ]);
    } catch (\Exception $e) {
        error_log('Get notifications error: ' . $e->getMessage());
        echo json_encode(['count' => 0, 'total' => 0, 'notifications' => []]);
    }
    exit;
}

// Handle AJAX notification count request (legacy)
if (isset($_GET['get_notif_count'])) {
    header('Content-Type: application/json');
    try {
        $total_count = $evaluations_collection->estimatedDocumentCount();
        echo json_encode(['count' => $total_count]);
    } catch (\Exception $e) {
        echo json_encode(['count' => 0]);
    }
    exit;
}

// Handle AJAX polling request
if (isset($_GET['check_new'])) {
    header('Content-Type: application/json');
    try {
        $latestEval = $evaluations_collection->findOne([], ['sort' => ['_id' => -1]]);
        $latestId = $latestEval ? (string)$latestEval['_id'] : null;
        
        // Compare with client's last known ID - treat empty string as null for first check
        $clientLastId = isset($_GET['lastId']) && $_GET['lastId'] !== '' ? $_GET['lastId'] : null;
        $hasNew = ($latestId && $latestId !== $clientLastId);
        
        // Debug logging
        error_log("Poll check - Latest: $latestId, Client: $clientLastId, HasNew: " . ($hasNew ? 'true' : 'false'));
        
        echo json_encode([
            'has_new' => $hasNew,
            'latest_id' => $latestId
        ]);
    } catch (\Exception $e) {
        error_log("Poll check error: " . $e->getMessage());
        echo json_encode(['has_new' => false, 'latest_id' => null]);
    }
    exit;
}

// Check if just logged in and unset the flag
$show_skeleton = isset($_SESSION['just_logged_in']) && $_SESSION['just_logged_in'];
if ($show_skeleton) {
    unset($_SESSION['just_logged_in']);
}

// Initialize variables with defaults
$total_teachers = 0;
$total_questions = 0;
$total_evaluations = 0;
$teacher_ratings = [];
$recent_evaluations = [];
$error = null;

// Get dashboard statistics
try {
    // Use estimatedDocumentCount() instead of countDocuments() for speed
    // This is approximate but MUCH faster for large collections
    $total_teachers = $teachers_collection->estimatedDocumentCount();
    $total_questions = $questions_collection->countDocuments(['status' => 'active']); // Keep exact for questions (usually small)
    $total_evaluations = $evaluations_collection->estimatedDocumentCount();
    
    // Get recent evaluations with teacher info (with field projection)
    $recent_evaluations = $evaluations_collection->find(
        [],
        [
            'projection' => ['teacher_id' => 1, 'submitted_at' => 1],
            'sort' => ['submitted_at' => -1],
            'limit' => 10
        ]
    );
    
    // Extract teacher IDs from recent evaluations
    $needed_teacher_ids = [];
    foreach ($recent_evaluations as $eval) {
        $teacher_id = $eval['teacher_id'] ?? null;
        if ($teacher_id) {
            $needed_teacher_ids[(string)$teacher_id] = true;
        }
    }
    
    // Fetch only the teachers we need (with field projection)
    $teachers_cache = [];
    if (count($needed_teacher_ids) > 0) {
        $teacher_ids_to_fetch = array_map(function($id) {
            return ctype_xdigit($id) && strlen($id) === 24 
                ? new MongoDB\BSON\ObjectId($id) 
                : $id;
        }, array_keys($needed_teacher_ids));
        
        $teachers_found = $teachers_collection->find(
            ['_id' => ['$in' => $teacher_ids_to_fetch]],
            ['projection' => ['first_name' => 1, 'last_name' => 1, 'middle_name' => 1, 'name' => 1]]
        );
        foreach ($teachers_found as $teacher) {
            $teacher_id_str = (string)$teacher['_id'];
            $teachers_cache[$teacher_id_str] = $teacher;
        }
    }
    
    // Reset the cursor for display - get again since we iterated it above (with field projection)
    $recent_evaluations = $evaluations_collection->find(
        [],
        [
            'projection' => ['teacher_id' => 1, 'submitted_at' => 1],
            'sort' => ['submitted_at' => -1],
            'limit' => 10
        ]
    );
    
    // TOP PERFORMERS: Load async via JavaScript to not block page load
    // For now, just use a simple placeholder
    $teacher_ratings = [];
    $top_performers = [];
    
    // Calculate key metrics
    $completion_rate = $total_teachers > 0 ? round(($total_evaluations / $total_teachers) * 100, 1) : 0;
    
    // Get top performers (top 3 teachers by rating)
    usort($teacher_ratings, function($a, $b) {
        return $b['avg_rating'] <=> $a['avg_rating'];
    });
    $top_performers = array_slice($teacher_ratings, 0, 3);
    
    // Calculate average rating
    $overall_avg_rating = count($teacher_ratings) > 0 
        ? round(array_sum(array_column($teacher_ratings, 'avg_rating')) / count($teacher_ratings), 2)
        : 0;
    
} catch (\Exception $e) {
    $error = 'Error fetching dashboard data: ' . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Teacher Evaluation System</title>
    
    <!-- Preload critical resources for faster rendering -->
    <link rel="preload" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" as="style">
    <link rel="preload" href="<?= ASSETS_URL ?>/css/dark-theme.css?v=2.0" as="style">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dark-theme.css?v=2.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- Async load non-critical styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" media="print" onload="this.media='all'">
    
    <!-- Defer non-critical scripts -->
    <script defer src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
        /* Light Modern Theme */
        body {
            background: #f8fafc !important;
            color: #000000;
        }
        
        .card {
            background: #ffffff !important;
            border-color: #e2e8f0 !important;
            color: #000000;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .card-header {
            background: #f1f5f9 !important;
            border-color: #e2e8f0 !important;
            color: #000000 !important;
        }
        
        .card-body {
            color: #000000;
        }
        
        .table {
            color: #000000;
        }
        
        .table-light {
            background: #f1f5f9 !important;
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
            background: #f1f5f9 !important;
            animation: none !important;
            transition: none !important;
            animation-play-state: paused !important;
        }
        
        .table .thead-light th {
            background: #f1f5f9 !important;
            color: #1e293b !important;
            border-color: #e2e8f0 !important;
        }
        
        .text-muted {
            color: #000000 !important;
        }
        
        .btn-primary {
            background: #8b5cf6 !important;
            border-color: #8b5cf6 !important;
            color: #fff !important;
        }
        
        .btn-primary:hover {
            background: #7c3aed !important;
        }
        
        .btn-info {
            background: #8b5cf6 !important;
            border-color: #8b5cf6 !important;
            color: #fff !important;
        }
        
        .btn-outline-primary {
            color: #8b5cf6 !important;
            border-color: #8b5cf6 !important;
        }
        
        .btn-outline-primary:hover {
            background: #8b5cf6 !important;
            color: #fff !important;
        }
        
        .badge {
            background: #8b5cf6 !important;
            color: #fff !important;
        }
        
        h1, h2, h3, h4, h5, h6 {
            color: #000000 !important;
        }
        
        /* Skeleton Loading */
        .skeleton {
            background: linear-gradient(90deg, #e2e8f0 25%, #f1f5f9 50%, #e2e8f0 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 200% 0;
            }
            100% {
                background-position: -200% 0;
            }
        }

        .skeleton-card {
            border: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            background: #f1f5f9 !important;
        }

        .skeleton-text {
            height: 20px;
            margin-bottom: 8px;
            border-radius: 4px;
        }

        .skeleton-title {
            height: 28px;
            margin-bottom: 16px;
            border-radius: 4px;
            width: 60%;
        }

        .skeleton-chart {
            height: 350px;
            border-radius: 8px;
        }

        .skeleton-table-row {
            height: 50px;
            margin-bottom: 8px;
            border-radius: 4px;
        }

        .content-loader {
            display: none;
            background: #f8fafc;
            min-height: 100vh;
        }

        .content-loader.active {
            display: block;
        }

        .skeleton-loader {
            display: none;
            background: #f8fafc;
        }

        .skeleton-loader.loading {
            display: block;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include '../includes/navbar.php'; ?>
    
    <!-- Main Content Wrapper -->
    <div class="main-content">
        <!-- Skeleton Loading -->
        <div class="skeleton-loader <?php echo $show_skeleton ? 'loading' : ''; ?>" data-show-skeleton="<?php echo $show_skeleton ? 'true' : 'false'; ?>">
            <div class="container-fluid py-5">
            <!-- Skeleton Header -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="skeleton skeleton-title" style="width: 200px;"></div>
                    <div class="skeleton skeleton-text" style="width: 250px;"></div>
                </div>
            </div>

            <!-- Skeleton Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card skeleton-card skeleton">
                        <div class="card-body">
                            <div class="skeleton skeleton-text" style="width: 80px;"></div>
                            <div class="skeleton skeleton-title" style="width: 60px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card skeleton-card skeleton">
                        <div class="card-body">
                            <div class="skeleton skeleton-text" style="width: 80px;"></div>
                            <div class="skeleton skeleton-title" style="width: 60px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card skeleton-card skeleton">
                        <div class="card-body">
                            <div class="skeleton skeleton-text" style="width: 80px;"></div>
                            <div class="skeleton skeleton-title" style="width: 60px;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card skeleton-card skeleton">
                        <div class="card-body">
                            <div class="skeleton skeleton-text" style="width: 80px;"></div>
                            <div class="skeleton skeleton-title" style="width: 60px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Skeleton Chart -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="skeleton skeleton-title"></div>
                            <div class="skeleton skeleton-chart"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="content-loader active">
        <div class="container-fluid py-5">
            <div class="row mb-4">
            <div class="col-md-8">
                <h1 class="h2">Dashboard</h1>
                <p class="text-muted">Welcome back, <?= escapeOutput($_SESSION['admin_username'] ?? 'Admin') ?>!</p>
            </div>
            <div class="col-md-4 text-end">
                <div id="toast-container"></div>
            </div>
        </div>
        
        <!-- Key Metrics Row -->
        <div class="row g-3 mt-2">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1 small">Overall Rating</p>
                                <h4 class="mb-0"><?= $overall_avg_rating ?>/5.0</h4>
                                <small class="text-warning"><i class="bi bi-star-fill"></i> Average score</small>
                            </div>
                            <div class="text-warning fs-4">
                                <i class="bi bi-award"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1 small">Completion Rate</p>
                                <h4 class="mb-0"><?= $completion_rate ?>%</h4>
                                <small class="text-success"><i class="bi bi-arrow-up"></i> Overall progress</small>
                            </div>
                            <div class="text-info fs-4">
                                <i class="bi bi-percent"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted mb-1 small">Active Teachers</p>
                                <h4 class="mb-0"><?= $total_teachers ?></h4>
                                <small class="text-primary"><i class="bi bi-person-fill"></i> Teaching staff</small>
                            </div>
                            <div class="text-primary fs-4">
                                <i class="bi bi-people-fill"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions Section -->
        <div class="row g-3 mt-3">
            <div class="col-md-3">
                <a href="questions.php" class="btn btn-primary w-100 py-2">
                    <i class="bi bi-plus-circle me-2"></i>Manage Questions
                </a>
            </div>
            <div class="col-md-3">
                <a href="teachers.php" class="btn btn-info w-100 py-2">
                    <i class="bi bi-people me-2"></i>Manage Teachers
                </a>
            </div>
            <div class="col-md-3">
                <a href="results.php" class="btn btn-success w-100 py-2">
                    <i class="bi bi-file-earmark me-2"></i>View Results
                </a>
            </div>
            <div class="col-md-3">
                <a href="analytics.php" class="btn btn-warning w-100 py-2">
                    <i class="bi bi-graph-up me-2"></i>Analytics
                </a>
            </div>
        </div>
        
        <!-- Top Performing Teachers -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light border-bottom">
                        <h5 class="mb-0"><i class="bi bi-trophy"></i> Top Performing Teachers</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($top_performers) > 0): ?>
                            <div class="row g-3">
                                <?php foreach ($top_performers as $index => $teacher): ?>
                                    <div class="col-md-4">
                                        <div class="card border-0" style="background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(6, 182, 212, 0.1) 100%);">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center mb-3">
                                                    <div class="badge bg-primary rounded-circle" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                                        <?= '#' . ($index + 1) ?>
                                                    </div>
                                                    <div class="ms-3 flex-grow-1">
                                                        <h6 class="mb-0"><?= htmlspecialchars($teacher['name'] ?? 'Anonymous') ?></h6>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-grow-1">
                                                        <small class="text-muted">Average Rating</small>
                                                        <div class="h5 mb-0"><?= $teacher['avg_rating'] ?>/5</div>
                                                    </div>
                                                    <div class="text-center">
                                                        <i class="bi bi-star-fill text-warning" style="font-size: 24px;"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size: 48px; color: #cbd5e1;"></i>
                                <p class="text-muted mt-2">No evaluation data available yet</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        
        <!-- Charts Row -->
        <div class="row g-4">
            <!-- Pie Chart: Evaluation Distribution -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Evaluation Status</h5>
                    </div>
                    <div class="card-body" style="height: 350px; display: flex; align-items: center;">
                        <canvas id="evaluationStatusChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Evaluations -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Recent Evaluations</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="recentEvaluationsTable" class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Teacher</th>
                                        <th>Date</th>
                                        <th>Average Rating</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $count = 0;
                                    foreach ($recent_evaluations as $eval) {
                                        if ($count >= 5) break;
                                        
                                        // Handle teacher_id - use cached teachers
                                        $teacher_name = 'Anonymous';
                                        $teacher_id = $eval['teacher_id'] ?? null;
                                        
                                        if ($teacher_id) {
                                            // Look up in cache instead of querying database
                                            $teacher_id_str = (string)$teacher_id;
                                            if (isset($teachers_cache[$teacher_id_str])) {
                                                $teacher = $teachers_cache[$teacher_id_str];
                                                $teacher_array = iterator_to_array($teacher);
                                                $full_name = trim(($teacher_array['first_name'] ?? '') . ' ' . ($teacher_array['middle_name'] ?? '') . ' ' . ($teacher_array['last_name'] ?? ''));
                                                $teacher_name = !empty($full_name) ? htmlspecialchars($full_name, ENT_QUOTES) : 'Anonymous';
                                            }
                                        }
                                        
                                        $avg_rating = 0;
                                        
                                        // Handle MongoDB DateTime object
                                        $eval_date = 'N/A';
                                        if (isset($eval['submitted_at'])) {
                                            try {
                                                $date_obj = $eval['submitted_at'];
                                                if ($date_obj instanceof MongoDB\BSON\UTCDateTime) {
                                                    $timestamp = $date_obj->toDateTime();
                                                    $timestamp->setTimezone(new \DateTimeZone('Asia/Manila'));
                                                    $eval_date = $timestamp->format('Y-m-d H:i:s');
                                                } elseif ($date_obj instanceof DateTime) {
                                                    $date_obj->setTimezone(new \DateTimeZone('Asia/Manila'));
                                                    $eval_date = $date_obj->format('Y-m-d H:i:s');
                                                }
                                            } catch (Exception $e) {
                                                $eval_date = 'N/A';
                                            }
                                        }
                                        
                                        if (isset($eval['answers'])) {
                                            // Convert MongoDB BSONArray to PHP array
                                            $answers_array = iterator_to_array($eval['answers']);
                                            $ratings = array_column($answers_array, 'rating');
                                            $avg_rating = round(array_sum($ratings) / count($ratings), 1);
                                        }
                                        
                                        echo '
                                        <tr>
                                            <td>' . $teacher_name . '</td>
                                            <td>' . htmlspecialchars($eval_date, ENT_QUOTES) . '</td>
                                            <td>
                                                <span class="badge bg-success">' . $avg_rating . '/5</span>
                                            </td>
                                            <td>
                                                <a href="results.php" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                        ';
                                        $count++;
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>  <!-- Close main-content -->

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="<?= ASSETS_URL ?>/js/main.js"></script>
    <script src="<?= ASSETS_URL ?>/js/confirmation.js"></script>
    <script src="<?= ASSETS_URL ?>/js/export-pdf.js"></script>
    <script>
        // Show skeleton only when logging in
        document.addEventListener('DOMContentLoaded', function() {
            const skeletonLoader = document.querySelector('.skeleton-loader');
            const showSkeleton = skeletonLoader && skeletonLoader.getAttribute('data-show-skeleton') === 'true';
            
            if (showSkeleton && skeletonLoader.classList.contains('loading')) {
                // Hide skeleton after 500ms for visual effect
                setTimeout(function() {
                    skeletonLoader.classList.remove('loading');
                }, 500);
            }
        });

        // Also hide skeleton immediately if page is fully loaded already
        if (document.readyState === 'complete') {
            const skeletonLoader = document.querySelector('.skeleton-loader');
            if (skeletonLoader && skeletonLoader.getAttribute('data-show-skeleton') === 'true') {
                skeletonLoader.classList.remove('loading');
            }
        }
    </script>
    
    <script>
        // Polling for new evaluations with toast notifications
        let lastEvalId = null;
        let isFirstLoad = true;
        
        function showToastNotification(message) {
            // Create light theme toast notification HTML
            const toastHtml = `
                <div class="toast-notification" style="
                    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                    color: #000000;
                    padding: 16px 20px;
                    border-radius: 12px;
                    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08), 0 2px 8px rgba(139, 92, 246, 0.1);
                    min-width: 320px;
                    font-weight: 500;
                    animation: slideInToast 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
                    margin-top: 10px;
                    border-left: 4px solid #8b5cf6;
                    display: flex;
                    align-items: flex-start;
                    gap: 12px;
                ">
                    <div style="
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        width: 40px;
                        height: 40px;
                        background: linear-gradient(135deg, rgba(139, 92, 246, 0.1) 0%, rgba(139, 92, 246, 0.05) 100%);
                        border-radius: 50%;
                        flex-shrink: 0;
                        border: 2px solid #8b5cf6;
                    ">
                        <i class="bi bi-check-circle" style="font-size: 22px; color: #8b5cf6;"></i>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; font-size: 14px; margin-bottom: 2px; color: #000000;">New Evaluation</div>
                        <div style="font-weight: 400; font-size: 13px; opacity: 0.85; color: #1a1a1a;">${message}</div>
                    </div>
                </div>
                <style>
                    @keyframes slideInToast {
                        from {
                            transform: translateX(400px);
                            opacity: 0;
                        }
                        to {
                            transform: translateX(0);
                            opacity: 1;
                        }
                    }
                    
                    @keyframes slideOutToast {
                        from {
                            transform: translateX(0);
                            opacity: 1;
                        }
                        to {
                            transform: translateX(400px);
                            opacity: 0;
                        }
                    }
                </style>
            `;
            
            const container = document.getElementById('toast-container');
            if (container) {
                container.insertAdjacentHTML('beforeend', toastHtml);
                
                // Remove after 5 seconds
                setTimeout(() => {
                    const toast = container.querySelector('.toast-notification');
                    if (toast) {
                        toast.style.animation = 'slideOutToast 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55) forwards';
                        setTimeout(() => toast.remove(), 400);
                    }
                }, 5000);
            }
        }
        
        function checkNewEvaluations() {
            const url = '/teacher-eval/admin/dashboard.php?check_new=1&lastId=' + (lastEvalId || '');
            console.log('🔍 Poll check - URL:', url, 'isFirstLoad:', isFirstLoad, 'lastEvalId:', lastEvalId);
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    console.log('📡 Poll response:', data);
                    
                    if (data.latest_id) {
                        console.log('✓ Latest ID exists:', data.latest_id);
                        console.log('  has_new:', data.has_new, 'isFirstLoad:', isFirstLoad);
                        
                        if (!isFirstLoad && data.has_new) {
                            // New evaluation detected!
                            console.log('🎉 NEW EVALUATION DETECTED - SHOWING NOTIFICATION!');
                            showToastNotification('📊 New evaluation submitted!');
                            
                            // Update notification badge if it exists
                            const notifBadge = document.getElementById('notif-badge');
                            if (notifBadge) {
                                let currentCount = parseInt(notifBadge.textContent) || 0;
                                notifBadge.textContent = currentCount + 1;
                                notifBadge.style.display = 'inline-block';
                            }
                            
                            // Reload after 5.5 seconds so user can see the toast fully (toast lasts 5 seconds)
                            setTimeout(() => {
                                console.log('🔄 Reloading page...');
                                location.reload();
                            }, 5500);
                        } else if (isFirstLoad) {
                            console.log('📌 First load - baseline set, ready for new evaluations');
                            isFirstLoad = false;
                        }
                        lastEvalId = data.latest_id;
                    } else {
                        console.log('⚠ No latest ID found');
                    }
                })
                .catch(error => console.log('❌ Poll check failed:', error.message));
        }
        
        // Start polling when page loads
        console.log('🚀 Starting evaluation polling...');
        checkNewEvaluations(); // First check to set baseline
        setInterval(checkNewEvaluations, 5000); // Check every 5 seconds (faster detection)
    </script>
    <script>
        // [Removed: Teacher Ratings Chart - Optimized dashboard layout]
        
        // Evaluation Status Pie Chart
        const statusCtx = document.getElementById('evaluationStatusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'Pending'],
                datasets: [{
                    data: [<?= $total_evaluations ?>, Math.max(0, <?= $total_teachers ?> - <?= $total_evaluations ?>)],
                    backgroundColor: ['#00d4ff', '#ffa500']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { labels: { color: '#a0a9b8' } }
                }
            }
        });
        
        // Initialize notification badge with recent evaluations count
        const notifBadge = document.getElementById('notif-badge');
        if (notifBadge && <?= $total_evaluations ?> > 0) {
            // Show badge if there are evaluations
            notifBadge.textContent = Math.min(<?= $total_evaluations ?>, 9) + (<?= $total_evaluations ?> > 9 ? '+' : '');
            notifBadge.style.display = 'inline-block';
        }
    </script>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>

