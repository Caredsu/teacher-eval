<?php
/**
 * Analytics Dashboard - Overall Evaluation Statistics & Charts
 * Shows comprehensive analytics across all evaluations
 */

require_once '../includes/helpers.php';
require_once '../config/database.php';

initializeSession();
requireLogin();

$success_msg = getSuccessMessage();
$error_msg = getErrorMessage();

// Get all evaluations
$evaluations = $evaluations_collection->find([])->toArray();
$teachers = $teachers_collection->find([])->toArray();

// Calculate statistics
$total_evaluations = count($evaluations);

$teacher_stats = [];
$overall_ratings = ['1' => 0, '2' => 0, '3' => 0, '4' => 0, '5' => 0];
$all_ratings = [];

// Process each evaluation
foreach ($evaluations as $eval) {
    $teacher_id = (string)$eval['teacher_id'];
    
    if (!isset($teacher_stats[$teacher_id])) {
        $teacher_stats[$teacher_id] = [
            'ratings' => [],
            'total_evals' => 0,
            'avg_rating' => 0
        ];
    }
    
    $teacher_stats[$teacher_id]['total_evals']++;
    
    // Handle new format: answers array
    if (isset($eval['answers'])) {
        // Convert MongoDB BSON array to PHP array
        $answers_array = iterator_to_array($eval['answers']);
        foreach ($answers_array as $answer) {
            $rating = (int)($answer['rating'] ?? 0);
            if ($rating > 0 && $rating <= 5) {
                $teacher_stats[$teacher_id]['ratings'][] = $rating;
                $overall_ratings[$rating]++;
                $all_ratings[] = $rating;
            }
        }
    }
    // Handle old format: ratings object (backward compatibility)
    elseif (isset($eval['ratings'])) {
        $ratings_array = iterator_to_array($eval['ratings']);
        foreach ($ratings_array as $rating) {
            $rating = (int)$rating;
            if ($rating > 0 && $rating <= 5) {
                $teacher_stats[$teacher_id]['ratings'][] = $rating;
                $overall_ratings[$rating]++;
                $all_ratings[] = $rating;
            }
        }
    }
}

// Calculate averages
foreach ($teacher_stats as $teacher_id => &$stats) {
    $stats['avg_rating'] = !empty($stats['ratings']) 
        ? round(array_sum($stats['ratings']) / count($stats['ratings']), 2) 
        : 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Teacher Evaluation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/teacher-eval/assets/css/dark-theme.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        body.dark-mode .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-card h3 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .stat-card .stat-value {
            font-size: 32px;
            font-weight: bold;
        }

        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 30px;
        }

        .badge-ratings {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin: 2px;
        }

        .badge-excellent {
            background-color: #198754;
            color: white;
        }

        .badge-good {
            background-color: #28a745;
            color: white;
        }

        .badge-average {
            background-color: #ffc107;
            color: #333;
        }

        .badge-poor {
            background-color: #fd7e14;
            color: white;
        }

        .badge-very-poor {
            background-color: #dc3545;
            color: white;
        }

        body.dark-mode .badge-average {
            color: #333;
        }

        .teacher-rating-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        body.dark-mode .teacher-rating-row {
            border-bottom-color: #444;
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
                <h1 class="h2"><i class="bi bi-graph-up"></i> Analytics Dashboard</h1>
            </div>
            <div class="col-md-6 text-end">
                <button class="btn btn-outline-secondary me-2" onclick="exportAnalyticsPDF()">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </button>
                <a href="/teacher-eval/admin/export-evaluations.php" class="btn btn-info">
                    <i class="bi bi-download"></i> Export CSV
                </a>
            </div>
        </div>

        <?php if ($success_msg): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> <?= escapeOutput($success_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-circle"></i> <?= escapeOutput($error_msg) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Summary Statistics - Simple Design -->
        <div class="row g-3 mb-4">
            <div class="col-lg-3 col-md-6">
                <div style="background: #2c3e50; border: 1px solid #3d5066; border-radius: 10px; padding: 20px; border-left: 4px solid #667eea; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <p style="margin: 0 0 8px 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">📊 Total Evaluations</p>
                        <h2 style="margin: 0; font-size: 28px; font-weight: 700; color: #ffffff;"><?= $total_evaluations ?></h2>
                    </div>
                    <div style="font-size: 32px; opacity: 0.3;">📈</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div style="background: #2c3e50; border: 1px solid #3d5066; border-radius: 10px; padding: 20px; border-left: 4px solid #764ba2; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <p style="margin: 0 0 8px 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">👨‍🏫 Teachers Evaluated</p>
                        <h2 style="margin: 0; font-size: 28px; font-weight: 700; color: #ffffff;"><?= count($teacher_stats) ?></h2>
                    </div>
                    <div style="font-size: 32px; opacity: 0.3;">👥</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div style="background: #2c3e50; border: 1px solid #3d5066; border-radius: 10px; padding: 20px; border-left: 4px solid #3498db; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <p style="margin: 0 0 8px 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">⭐ Overall Average</p>
                        <h2 style="margin: 0; font-size: 28px; font-weight: 700; color: #ffffff;"><?= !empty($all_ratings) ? number_format(array_sum($all_ratings) / count($all_ratings), 2) : '0.00' ?>/5</h2>
                    </div>
                    <div style="font-size: 32px; opacity: 0.3;">🌟</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div style="background: #2c3e50; border: 1px solid #3d5066; border-radius: 10px; padding: 20px; border-left: 4px solid #f39c12; display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <p style="margin: 0 0 8px 0; color: #999; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">📝 Total Ratings</p>
                        <h2 style="margin: 0; font-size: 28px; font-weight: 700; color: #ffffff;"><?= count($all_ratings) ?></h2>
                    </div>
                    <div style="font-size: 32px; opacity: 0.3;">💬</div>
                </div>
            </div>
        </div>

        <!-- Rating Distribution Chart -->
        <div class="row mt-5">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-pie-chart"></i> Rating Distribution</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="distributionChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Teachers -->
        <div class="row mt-5">
            <div class="col-md-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-star-fill"></i> Teacher Ratings Summary</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($teacher_stats) > 0): ?>
                            <div style="max-height: 600px; overflow-y: auto;">
                                <?php foreach ($teachers as $teacher): 
                                    $teacher_id = (string)$teacher['_id'];
                                    if (!isset($teacher_stats[$teacher_id])) continue;
                                    
                                    $stats = $teacher_stats[$teacher_id];
                                    $overall_avg = $stats['avg_rating'];
                                    $teacher_name = formatFullName((string)($teacher['first_name'] ?? ''), (string)($teacher['middle_name'] ?? ''), (string)($teacher['last_name'] ?? ''));
                                    
                                    // Determine badge class
                                    if ($overall_avg >= 4.5) $badge_class = 'badge-excellent';
                                    elseif ($overall_avg >= 4.0) $badge_class = 'badge-good';
                                    elseif ($overall_avg >= 3.0) $badge_class = 'badge-average';
                                    elseif ($overall_avg >= 2.0) $badge_class = 'badge-poor';
                                    else $badge_class = 'badge-very-poor';
                                ?>
                                <div class="teacher-rating-row">
                                    <div>
                                        <div style="font-weight: 600; margin-bottom: 8px;"><?= escapeOutput($teacher_name) ?></div>
                                        <small class="text-muted"><?= escapeOutput($teacher['department'] ?? '') ?> • <?= $stats['total_evals'] ?> evaluations</small>
                                        <div style="margin-top: 8px;">
                                            <span class="badge-ratings badge-excellent">Avg Rating: <?= number_format($stats['avg_rating'], 1) ?>/5</span>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div class="badge-ratings <?= $badge_class ?>" style="font-size: 16px; padding: 10px 16px;">
                                            <?= number_format($overall_avg, 2) ?>/5.0
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center py-4">No evaluation data available yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>  <!-- Close main-content -->

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="/teacher-eval/assets/js/main.js"></script>
    <script src="/teacher-eval/assets/js/export-pdf.js"></script>
    <script>
        // Rating Distribution Chart
        const distributionCtx = document.getElementById('distributionChart').getContext('2d');
        new Chart(distributionCtx, {
            type: 'doughnut',
            data: {
                labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
                datasets: [{
                    data: [
                        <?= $overall_ratings['1'] ?? 0 ?>,
                        <?= $overall_ratings['2'] ?? 0 ?>,
                        <?= $overall_ratings['3'] ?? 0 ?>,
                        <?= $overall_ratings['4'] ?? 0 ?>,
                        <?= $overall_ratings['5'] ?? 0 ?>
                    ],
                    backgroundColor: [
                        '#dc3545',
                        '#fd7e14',
                        '#ffc107',
                        '#20c997',
                        '#198754'
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { padding: 15 }
                    }
                }
            }
        });
    </script>
    
    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>
</body>
</html>
