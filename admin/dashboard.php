<?php
/**
 * Admin Dashboard
 */

require_once '../includes/helpers.php';
require_once '../config/database.php';

initializeSession();
requireLogin();

// Check if just logged in and unset the flag
$show_skeleton = isset($_SESSION['just_logged_in']) && $_SESSION['just_logged_in'];
if ($show_skeleton) {
    unset($_SESSION['just_logged_in']);
}

// Get dashboard statistics
try {
    $total_teachers = $teachers_collection->countDocuments();
    $total_questions = $questions_collection->countDocuments();
    $total_evaluations = $evaluations_collection->countDocuments();
    
    // Get recent evaluations with teacher info
    $recent_evaluations = $evaluations_collection->find(
        [],
        ['sort' => ['submitted_at' => -1], 'limit' => 10]
    );
    
    // Get teacher rating averages
    $teacher_ratings = [];
    $teachers = $teachers_collection->find();
    
    foreach ($teachers as $teacher) {
        $teacher_id = (string) $teacher['_id'];
        $avg_rating = $evaluations_collection->aggregate([
            [
                '$match' => ['teacher_id' => $teacher_id]
            ],
            [
                '$unwind' => '$answers'
            ],
            [
                '$group' => [
                    '_id' => null,
                    'avg_rating' => ['$avg' => '$answers.rating']
                ]
            ]
        ]);
        
        $avg_rating_value = 0;
        foreach ($avg_rating as $result) {
            $avg_rating_value = round($result['avg_rating'], 2);
        }
        
        $teacher_ratings[] = [
            'name' => $teacher['name'] ?? 'Unknown',
            'avg_rating' => $avg_rating_value
        ];
    }
    
} catch (\Exception $e) {
    $error = 'Error fetching dashboard data: ' . $e->getMessage();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Teacher Evaluation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/teacher-eval/assets/css/dark-theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
        /* Dark Minimalist Theme */
        body {
            background: #1e2a3a !important;
            color: #ecf0f1;
        }
        
        .card {
            background: #2c3e50 !important;
            border-color: #475569 !important;
            color: #ecf0f1;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        
        .card-header {
            background: #34495e !important;
            border-color: #475569 !important;
            color: #ecf0f1 !important;
        }
        
        .card-body {
            color: #ecf0f1;
        }
        
        .table {
            color: #bdc3c7;
        }
        
        .table-light {
            background: #34495e !important;
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
            background: #475569 !important;
            animation: none !important;
            transition: none !important;
            animation-play-state: paused !important;
        }
        
        .table .thead-light th {
            background: #34495e !important;
            color: #ecf0f1 !important;
            border-color: #475569 !important;
        }
        
        .text-muted {
            color: #a0a9b8 !important;
        }
        
        .btn-primary {
            background: #3498db !important;
            border-color: #3498db !important;
            color: #fff !important;
        }
        
        .btn-primary:hover {
            background: #2980b9 !important;
        }
        
        .btn-info {
            background: #3498db !important;
            border-color: #3498db !important;
            color: #fff !important;
        }
        
        .btn-outline-primary {
            color: #3498db !important;
            border-color: #3498db !important;
        }
        
        .btn-outline-primary:hover {
            background: #3498db !important;
            color: #fff !important;
        }
        
        .badge {
            background: #3498db !important;
            color: #fff !important;
        }
        
        h1, h2, h3, h4, h5, h6 {
            color: #ecf0f1 !important;
        }
        
        /* Skeleton Loading */
        .skeleton {
            background: linear-gradient(90deg, #0f3460 25%, #1a3050 50%, #0f3460 75%);
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
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            background: #16213e !important;
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
            background: #1a1e2e;
            min-height: 100vh;
        }

        .content-loader.active {
            display: block;
        }

        .skeleton-loader {
            display: none;
            background: #1a1e2e;
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
                <a href="teachers.php" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus"></i> Add Teacher
                </a>
                <a href="questions.php" class="btn btn-info btn-sm">
                    <i class="bi bi-plus"></i> Add Question
                </a>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <div class="row g-4 mb-4">
            <!-- Teachers Card -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Total Teachers</p>
                                <h3 class="mb-0"><?= $total_teachers ?></h3>
                            </div>
                            <div class="text-primary fs-1 opacity-50">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Questions Card -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Questions</p>
                                <h3 class="mb-0"><?= $total_questions ?></h3>
                            </div>
                            <div class="text-success fs-1 opacity-50">
                                <i class="bi bi-question-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Evaluations Card -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Total Evaluations</p>
                                <h3 class="mb-0"><?= $total_evaluations ?></h3>
                            </div>
                            <div class="text-info fs-1 opacity-50">
                                <i class="bi bi-bar-chart"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Average Rating Card -->
            <div class="col-md-3">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <p class="text-muted mb-1">Avg Rating</p>
                                <h3 class="mb-0">
                                    <?php
                                    if (count($teacher_ratings) > 0) {
                                        $avg = array_sum(array_column($teacher_ratings, 'avg_rating')) / count($teacher_ratings);
                                        echo round($avg, 1);
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </h3>
                            </div>
                            <div class="text-warning fs-1 opacity-50">
                                <i class="bi bi-star"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Generate Report Card -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center text-center py-5">
                        <div class="fs-1 mb-3">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </div>
                        <h5 class="card-title mb-2">Generate Report</h5>
                        <p class="card-text mb-4 small" style="color: rgba(255,255,255,0.9);">Export comprehensive system report as PDF</p>
                        <button class="btn btn-light" onclick="exportDashboardReport()">
                            <i class="bi bi-download"></i> Generate PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Charts Row -->
        <div class="row g-4">
            <!-- Bar Chart: Teacher Ratings -->
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Teacher Average Ratings</h5>
                    </div>
                    <div class="card-body" style="height: 350px; display: flex; align-items: center;">
                        <?php if (count($teacher_ratings) > 0): ?>
                            <canvas id="teacherRatingsChart"></canvas>
                        <?php else: ?>
                            <p class="text-muted text-center my-5 w-100">No data available yet</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
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
                            <table class="table table-hover mb-0">
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
                                        
                                        // Handle teacher_id - it might be a string or ObjectId
                                        $teacher_name = 'Unknown';
                                        $teacher_id = $eval['teacher_id'] ?? null;
                                        
                                        if ($teacher_id) {
                                            $teacher = null;
                                            // Try as string first
                                            if (isValidObjectId((string)$teacher_id)) {
                                                $teacher = $teachers_collection->findOne(['_id' => new MongoDB\BSON\ObjectId((string)$teacher_id)]);
                                            } else {
                                                // Try as direct string match
                                                $teacher = $teachers_collection->findOne(['_id' => $teacher_id]);
                                            }
                                            
                                            if ($teacher) {
                                                $teacher_array = iterator_to_array($teacher);
                                                $teacher_name = isset($teacher_array['name']) ? htmlspecialchars((string)$teacher_array['name'], ENT_QUOTES) : 'Unknown';
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
    <script src="/teacher-eval/assets/js/main.js"></script>
    <script src="/teacher-eval/assets/js/confirmation.js"></script>
    <script src="/teacher-eval/assets/js/export-pdf.js"></script>
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
        // Teacher Ratings Bar Chart
        <?php if (count($teacher_ratings) > 0): ?>
        const teacherCtx = document.getElementById('teacherRatingsChart').getContext('2d');
        new Chart(teacherCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($teacher_ratings, 'name')) ?>,
                datasets: [{
                    label: 'Average Rating',
                    data: <?= json_encode(array_column($teacher_ratings, 'avg_rating')) ?>,
                    backgroundColor: '#00d4ff',
                    borderColor: '#00d4ff',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true, labels: { color: '#a0a9b8' } }
                },
                scales: {
                    y: { beginAtZero: true, max: 5, ticks: { color: '#a0a9b8' }, grid: { color: '#0f3460' } },
                    x: { ticks: { color: '#a0a9b8' }, grid: { color: '#0f3460' } }
                }
            }
        });
        <?php endif; ?>
        
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
    </script>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>

