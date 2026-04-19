<?php
/**
 * Admin Dashboard - Main Page with Multi-Tab Interface
 */

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

// Add HTTP cache headers (3 minutes)
header('Cache-Control: public, max-age=180'); // 3 minutes
header('Pragma: cache');

// Enable gzip compression if available
if (!ob_get_contents() && extension_loaded('zlib')) {
    ob_start('ob_gzhandler');
}

use MongoDB\BSON\ObjectId;

$db = Database::getInstance();
$teachersCollection = $db->getCollection('teachers');
$evaluationsCollection = $db->getCollection('evaluations');

// Use aggregation pipeline instead of N+1 queries!
$teacherStats = [];
try {
    // Optimized pipeline: project fields early, limit scope, then aggregate
    $pipeline = [
        // Project only fields we need
        [
            '$project' => [
                'teacher_id' => 1,
                'ratings' => 1
            ]
        ],
        // Group by teacher
        [
            '$group' => [
                '_id' => '$teacher_id',
                'count' => ['$sum' => 1],
                'avgTeaching' => ['$avg' => '$ratings.teaching'],
                'avgCommunication' => ['$avg' => '$ratings.communication'],
                'avgKnowledge' => ['$avg' => '$ratings.knowledge'],
            ]
        ],
        // Sort by count descending
        ['$sort' => ['count' => -1]],
        // Limit to top 100 teachers
        ['$limit' => 100]
    ];
    
    $statsResult = $evaluationsCollection->aggregate($pipeline);
    foreach ($statsResult as $stat) {
        $teacherId = (string)$stat['_id'];
        $teaching = $stat['avgTeaching'] ?? 0;
        $communication = $stat['avgCommunication'] ?? 0;
        $knowledge = $stat['avgKnowledge'] ?? 0;
        
        $teacherStats[$teacherId] = [
            'count' => $stat['count'],
            'avgTeaching' => round($teaching, 2),
            'avgCommunication' => round($communication, 2),
            'avgKnowledge' => round($knowledge, 2),
            'overallAvg' => round(($teaching + $communication + $knowledge) / 3, 2)
        ];
    }
} catch (\Exception $e) {
    error_log('Aggregation error: ' . $e->getMessage());
}

// Get only teachers that have evaluations (limit to 100 for performance, with field projection)
$teachers = $teachersCollection->find(
    [],
    [
        'projection' => [
            'first_name' => 1,
            'last_name' => 1,
            'middle_name' => 1,
            'department' => 1,
            'email' => 1,
            'status' => 1
        ],
        'limit' => 100
    ]
)->toArray();

// Calculate overall statistics - use aggregation instead of loading all data
$totalEvaluations = $evaluationsCollection->estimatedDocumentCount();
$avgOverallRating = 0;
try {
    $statsResult = $evaluationsCollection->aggregate([
        [
            '$group' => [
                '_id' => null,
                'avgTeaching' => ['$avg' => '$ratings.teaching'],
                'avgCommunication' => ['$avg' => '$ratings.communication'],
                'avgKnowledge' => ['$avg' => '$ratings.knowledge'],
            ]
        ]
    ])->toArray();
    
    if (count($statsResult) > 0) {
        $stats = $statsResult[0];
        $teaching = $stats['avgTeaching'] ?? 0;
        $communication = $stats['avgCommunication'] ?? 0;
        $knowledge = $stats['avgKnowledge'] ?? 0;
        $avgOverallRating = round(($teaching + $communication + $knowledge) / 3, 2);
    }
} catch (\Exception $e) {
    error_log('Overall stats aggregation error: ' . $e->getMessage());
}

// Get top-rated teachers (by overall average)
$topTeachers = array_filter($teachers, function($teacher) use ($teacherStats) {
    $tid = (string)$teacher['_id'];
    return $teacherStats[$tid]['count'] > 0;
});
usort($topTeachers, function($a, $b) use ($teacherStats) {
    $tidA = (string)$a['_id'];
    $tidB = (string)$b['_id'];
    return $teacherStats[$tidB]['overallAvg'] <=> $teacherStats[$tidA]['overallAvg'];
});
$topTeachers = array_slice($topTeachers, 0, 3);

// Get lowest-rated teachers
$bottomTeachers = array_reverse($topTeachers);
$bottomTeachers = array_slice($bottomTeachers, 0, 3);

// Determine current tab
$currentTab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Teacher Evaluation</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #1a1e2e;
            min-height: 100vh;
        }
        
        /* Top Navbar */
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
        }
        
        .nav-brand {
            font-size: 20px;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nav-user {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            font-size: 14px;
        }
        
        .user-info p {
            margin: 2px 0;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 1px solid white;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }
        
        /* Tab Navigation */
        .tabs-container {
            background: #16213e;
            border-bottom: 2px solid #0f3460;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        
        .tabs {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            padding: 0;
        }
        
        .tab-btn {
            background: none;
            border: none;
            padding: 15px 30px;
            font-size: 16px;
            font-weight: 600;
            color: #a0a9b8;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            position: relative;
        }
        
        .tab-btn:hover {
            color: #00d4ff;
        }
        
        .tab-btn.active {
            color: #00d4ff;
            border-bottom-color: #00d4ff;
        }
        
        /* Main Content */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        .header {
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 28px;
            color: #e0e0e0;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #a0a9b8;
        }
        
        /* Tab Content */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* Stats Cards Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: #16213e;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.3);
            transition: transform 0.3s, box-shadow 0.3s;
            border-left: 4px solid #00d4ff;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 25px rgba(0,212,255,0.2);
        }
        
        .stat-label {
            color: #a0a9b8;
            font-size: 14px;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #00d4ff;
        }
        
        /* Sections */
        .section {
            background: #16213e;
            border-radius: 10px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.3);
            padding: 25px;
            margin-bottom: 25px;
        }
        
        .section h2 {
            color: #e0e0e0;
            margin-bottom: 20px;
            font-size: 20px;
            border-bottom: 2px solid #00d4ff;
            padding-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        /* Teachers Grid */
        .teachers-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .teacher-card {
            background: #0f3460;
            border-radius: 8px;
            padding: 20px;
            border-left: 4px solid #00d4ff;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .teacher-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,212,255,0.2);
        }
        
        .teacher-name {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 8px;
            color: #e0e0e0;
        }
        
        .teacher-dept {
            display: inline-block;
            background: #00d4ff;
            color: #1a1e2e;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-bottom: 12px;
            font-weight: 600;
        }
        
        .rating-item {
            display: flex;
            justify-content: space-between;
            margin: 6px 0;
            font-size: 14px;
        }
        
        .rating-label {
            color: #a0a9b8;
        }
        
        .rating-star {
            font-weight: 600;
            color: #00d4ff;
        }
        
        .rating-star.high {
            color: #4a4;
        }
        
        .rating-star.low {
            color: #f44;
        }
        
        /* Teachers Table */
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #0f3460;
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #e0e0e0;
            border-bottom: 2px solid #00d4ff;
        }
        
        td {
            padding: 15px;
            border-bottom: 1px solid #0f3460;
            color: #a0a9b8;
        }
        
        tr:hover {
            background: #0f3460;
        }
        
        .teacher-col {
            font-weight: 600;
            color: #e0e0e0;
        }
        
        .dept-badge {
            display: inline-block;
            background: #00d4ff;
            color: #1a1e2e;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .rating {
            font-weight: 600;
            color: #00d4ff;
        }
        
        .rating.low {
            color: #f44;
        }
        
        .rating.high {
            color: #4a4;
        }
        
        .eval-count {
            background: #0f3460;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            color: #a0a9b8;
        }
        
        .action-btn {
            background: #00d4ff;
            color: #1a1e2e;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
            transition: background 0.3s;
            font-weight: 600;
        }
        
        .action-btn.delete {
            background: #f44;
            color: white;
        }
        
        .action-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-excellent {
            background: #1a3a2a;
            color: #4a4;
        }
        
        .status-good {
            background: #1a2a3a;
            color: #00d4ff;
        }
        
        .status-fair {
            background: #3a2a1a;
            color: #ffa500;
        }
        
        .status-poor {
            background: #3a1a1a;
            color: #f44;
        }
        
        .results-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .result-card {
            background: #0f3460;
            border: 1px solid #1a3050;
            border-radius: 8px;
            padding: 20px;
        }
        
        .result-card h3 {
            color: #e0e0e0;
            margin-bottom: 15px;
            font-size: 16px;
        }
        
        .result-stat {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #1a3050;
        }
        
        .result-stat:last-child {
            border-bottom: none;
        }
        
        .result-label {
            color: #a0a9b8;
        }
        
        .result-value {
            font-weight: 600;
            color: #00d4ff;
        }
        
        .no-data {
            color: #666;
            text-align: center;
            padding: 40px;
        }
        
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .tabs {
                flex-wrap: wrap;
            }
            
            .tab-btn {
                padding: 10px 15px;
                font-size: 14px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .teachers-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                🎓 Teacher Evaluation System
            </div>
            <div class="nav-user">
                <div class="user-info">
                    <p>Welcome, <strong><?= htmlspecialchars($_SESSION['username']) ?></strong></p>
                    <p style="font-size: 12px; opacity: 0.9;"><?= ucfirst($_SESSION['role']) ?></p>
                </div>
                <form method="POST" action="logout.php" style="display: inline;">
                    <button type="submit" class="logout-btn">Logout</button>
                </form>
            </div>
        </div>
    </nav>
    
    <!-- Tab Navigation -->
    <div class="tabs-container">
        <div class="tabs">
            <button class="tab-btn <?= $currentTab === 'dashboard' ? 'active' : '' ?>" onclick="switchTab('dashboard')">
                📊 Dashboard
            </button>
            <button class="tab-btn <?= $currentTab === 'teachers' ? 'active' : '' ?>" onclick="switchTab('teachers')">
                👨‍🏫 Teachers
            </button>
            <button class="tab-btn <?= $currentTab === 'results' ? 'active' : '' ?>" onclick="switchTab('results')">
                📈 Results
            </button>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <!-- Dashboard Tab -->
        <div id="dashboard" class="tab-content <?= $currentTab === 'dashboard' ? 'active' : '' ?>">
            <div class="header">
                <h1>📊 Dashboard</h1>
                <p>Real-time overview of teacher evaluations</p>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Total Teachers</div>
                    <div class="stat-value"><?= count($teachers) ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Total Evaluations</div>
                    <div class="stat-value"><?= $totalEvaluations ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Overall Average Rating</div>
                    <div class="stat-value"><?= $avgOverallRating ?> ⭐</div>
                </div>
                <div class="stat-card">
                    <div class="stat-label">Evaluation Period</div>
                    <div class="stat-value" style="font-size: 18px;"><?= date('Y-m-d') ?></div>
                </div>
            </div>
            
            <!-- Top Rated Teachers -->
            <div class="section">
                <h2>⭐ Top-Rated Teachers</h2>
                <?php if (count($topTeachers) > 0): ?>
                    <div class="teachers-grid">
                        <?php foreach ($topTeachers as $teacher):
                            $tid = (string)$teacher['_id'];
                            $stats = $teacherStats[$tid];
                        ?>
                            <div class="teacher-card">
                                <div class="teacher-name"><?= htmlspecialchars($teacher['firstname'] . ' ' . $teacher['lastname']) ?></div>
                                <span class="teacher-dept"><?= htmlspecialchars($teacher['department']) ?></span>
                                <div style="margin-bottom: 12px; padding-top: 12px; border-top: 1px solid rgba(0,0,0,0.1);">
                                    <div class="rating-item">
                                        <span class="rating-label">Teaching:</span>
                                        <span class="rating-star high"><?= $stats['avgTeaching'] ?> ★</span>
                                    </div>
                                    <div class="rating-item">
                                        <span class="rating-label">Communication:</span>
                                        <span class="rating-star high"><?= $stats['avgCommunication'] ?> ★</span>
                                    </div>
                                    <div class="rating-item">
                                        <span class="rating-label">Knowledge:</span>
                                        <span class="rating-star high"><?= $stats['avgKnowledge'] ?> ★</span>
                                    </div>
                                    <div class="rating-item" style="font-weight: 700; font-size: 16px; margin-top: 10px;">
                                        <span class="rating-label">Overall:</span>
                                        <span class="rating-star high"><?= $stats['overallAvg'] ?></span>
                                    </div>
                                </div>
                                <div style="font-size: 12px; color: #666; margin-top: 10px;">
                                    <span class="eval-count"><?= $stats['count'] ?> evaluations</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">📭 No evaluations yet</div>
                <?php endif; ?>
            </div>
            
            <!-- Needs Improvement Teachers -->
            <?php if (count($bottomTeachers) > 0): ?>
            <div class="section">
                <h2>⚠️ Teachers Needing Support</h2>
                <div class="teachers-grid">
                    <?php foreach ($bottomTeachers as $teacher):
                        $tid = (string)$teacher['_id'];
                        $stats = $teacherStats[$tid];
                    ?>
                        <div class="teacher-card" style="border-left-color: #f44;">
                            <div class="teacher-name"><?= htmlspecialchars($teacher['firstname'] . ' ' . $teacher['lastname']) ?></div>
                            <span class="teacher-dept" style="background: #f44;"><?= htmlspecialchars($teacher['department']) ?></span>
                            <div style="margin-bottom: 12px; padding-top: 12px; border-top: 1px solid rgba(0,0,0,0.1);">
                                <div class="rating-item">
                                    <span class="rating-label">Teaching:</span>
                                    <span class="rating-star low"><?= $stats['avgTeaching'] ?> ★</span>
                                </div>
                                <div class="rating-item">
                                    <span class="rating-label">Communication:</span>
                                    <span class="rating-star low"><?= $stats['avgCommunication'] ?> ★</span>
                                </div>
                                <div class="rating-item">
                                    <span class="rating-label">Knowledge:</span>
                                    <span class="rating-star low"><?= $stats['avgKnowledge'] ?> ★</span>
                                </div>
                                <div class="rating-item" style="font-weight: 700; font-size: 16px; margin-top: 10px;">
                                    <span class="rating-label">Overall:</span>
                                    <span class="rating-star low"><?= $stats['overallAvg'] ?></span>
                                </div>
                            </div>
                            <div style="font-size: 12px; color: #666; margin-top: 10px;">
                                <span class="eval-count"><?= $stats['count'] ?> evaluations</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Teachers Tab -->
        <div id="teachers" class="tab-content <?= $currentTab === 'teachers' ? 'active' : '' ?>">
            <div class="header">
                <h1>👨‍🏫 Teachers Management</h1>
                <p>Manage all teachers in the system</p>
            </div>
            
            <div class="section">
                <h2>Teachers List & Evaluations</h2>
                
                <?php if (count($teachers) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Teacher Name</th>
                                <th>Department</th>
                                <th>Evaluations</th>
                                <th>Teaching</th>
                                <th>Communication</th>
                                <th>Knowledge</th>
                                <th>Overall</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($teachers as $teacher): 
                                $tid = (string)$teacher['_id'];
                                $stats = $teacherStats[$tid];
                            ?>
                                <tr>
                                    <td class="teacher-col"><?= htmlspecialchars($teacher['firstname'] . ' ' . $teacher['lastname']) ?></td>
                                    <td><span class="dept-badge"><?= htmlspecialchars($teacher['department']) ?></span></td>
                                    <td><span class="eval-count"><?= $stats['count'] ?></span></td>
                                    <td class="rating <?= $stats['avgTeaching'] >= 4 ? 'high' : 'low' ?>"><?= $stats['avgTeaching'] ?> ★</td>
                                    <td class="rating <?= $stats['avgCommunication'] >= 4 ? 'high' : 'low' ?>"><?= $stats['avgCommunication'] ?> ★</td>
                                    <td class="rating <?= $stats['avgKnowledge'] >= 4 ? 'high' : 'low' ?>"><?= $stats['avgKnowledge'] ?> ★</td>
                                    <td class="rating <?= $stats['overallAvg'] >= 4 ? 'high' : 'low' ?>" style="font-weight: bold;"><?= $stats['overallAvg'] ?></td>
                                    <td>
                                        <button class="action-btn" onclick="alert('Edit functionality coming soon')">Edit</button>
                                        <button class="action-btn delete" onclick="alert('Delete functionality coming soon')">Delete</button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="no-data">ℹ️ No teachers found in the system</div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Results Tab -->
        <div id="results" class="tab-content <?= $currentTab === 'results' ? 'active' : '' ?>">
            <div class="header">
                <h1>📈 Evaluation Results</h1>
                <p>Detailed evaluation results and performance analysis</p>
            </div>
            
            <div class="section">
                <h2>Teachers Performance Summary</h2>
                
                <?php if (count($teachers) > 0): ?>
                    <div class="results-grid">
                        <?php foreach ($teachers as $teacher):
                            $tid = (string)$teacher['_id'];
                            $stats = $teacherStats[$tid];
                            
                            // Determine status
                            $status = 'poor';
                            $statusClass = 'status-poor';
                            if ($stats['overallAvg'] >= 4.5) {
                                $status = 'Excellent';
                                $statusClass = 'status-excellent';
                            } elseif ($stats['overallAvg'] >= 4) {
                                $status = 'Good';
                                $statusClass = 'status-good';
                            } elseif ($stats['overallAvg'] >= 3) {
                                $status = 'Fair';
                                $statusClass = 'status-fair';
                            } else {
                                $status = 'Needs Improvement';
                                $statusClass = 'status-poor';
                            }
                        ?>
                            <div class="result-card">
                                <h3>
                                    <?= htmlspecialchars($teacher['firstname'] . ' ' . $teacher['lastname']) ?>
                                    <span class="status-badge <?= $statusClass ?>" style="float: right; margin-top: -5px;">
                                        <?= $status ?>
                                    </span>
                                </h3>
                                
                                <div class="result-stat">
                                    <span class="result-label">Department:</span>
                                    <span class="result-value"><?= htmlspecialchars($teacher['department']) ?></span>
                                </div>
                                <div class="result-stat">
                                    <span class="result-label">Total Evaluations:</span>
                                    <span class="result-value"><?= $stats['count'] ?></span>
                                </div>
                                <div class="result-stat">
                                    <span class="result-label">Teaching Rating:</span>
                                    <span class="result-value"><?= $stats['avgTeaching'] ?> / 5.0</span>
                                </div>
                                <div class="result-stat">
                                    <span class="result-label">Communication Rating:</span>
                                    <span class="result-value"><?= $stats['avgCommunication'] ?> / 5.0</span>
                                </div>
                                <div class="result-stat">
                                    <span class="result-label">Knowledge Rating:</span>
                                    <span class="result-value"><?= $stats['avgKnowledge'] ?> / 5.0</span>
                                </div>
                                <div class="result-stat" style="font-weight: bold; border-top: 2px solid #667eea; padding-top: 10px; margin-top: 10px;">
                                    <span class="result-label">Overall Average:</span>
                                    <span class="result-value" style="font-size: 18px;"><?= $stats['overallAvg'] ?> / 5.0</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-data">📭 No evaluation data available</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked button
            event.target.classList.add('active');
            
            // Update URL
            window.history.replaceState({}, '', '?tab=' + tabName);
        }
    </script>
</body>
</html>
