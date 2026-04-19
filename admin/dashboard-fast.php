<?php
/**
 * Ultra-Fast Dashboard - Minimal Dependencies
 */

require_once '../includes/helpers.php';
require_once '../config/database.php';

// Fast cache headers
header('Cache-Control: public, max-age=60');
header('Pragma: cache');

initializeSession();
requireLogin();

$error = null;

try {
    $total_teachers = $teachers_collection->estimatedDocumentCount();
    $total_questions = $questions_collection->countDocuments(['status' => 'active']);
    $total_evaluations = $evaluations_collection->estimatedDocumentCount();
    
    // Get recent evaluations (lightweight query)
    $recent = $evaluations_collection->find(
        [],
        ['sort' => ['submitted_at' => -1], 'limit' => 5, 'projection' => ['_id' => 1, 'submitted_at' => 1]]
    )->toArray();
    
    $completion_rate = $total_teachers > 0 ? round(($total_evaluations / $total_teachers) * 100, 1) : 0;
    
} catch (Exception $e) {
    $error = 'Error loading dashboard data';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Dashboard - Admin</title>
    
    <!-- CRITICAL STYLES INLINED -->
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html { font-size: 16px; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: #f8f9fa;
            color: #333;
            line-height: 1.5;
        }
        
        nav {
            background: #1f2937;
            padding: 1rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        nav a { color: white; margin-right: 1.5rem; text-decoration: none; }
        nav a:hover { color: #3b82f6; }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        
        h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .subtitle { color: #6b7280; font-size: 0.9rem; margin-bottom: 2rem; }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1f2937;
        }
        
        .stat-sub {
            color: #9ca3af;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }
        
        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 1rem;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #667eea, #764ba2);
            width: var(--progress, 0%);
            transition: width 0.3s;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .card-header {
            background: #f3f4f6;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e5e7eb;
            font-weight: 600;
            color: #1f2937;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f9fafb;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
        }
        
        td {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        tr:hover { background: #f9fafb; }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
        }
        
        .btn:hover { background: #2563eb; }
        
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #7f1d1d;
            border-left: 4px solid #dc2626;
        }
        
        @media (max-width: 768px) {
            .stats { grid-template-columns: 1fr; }
            .container { padding: 1rem 0.5rem; }
        }
    </style>
</head>
<body>
    <nav>
        <div><strong>Teacher Evaluation System</strong></div>
        <div>
            <a href="teachers.php">Teachers</a>
            <a href="results.php">Results</a>
            <a href="analytics.php">Analytics</a>
            <a href="logout.php">Logout</a>
        </div>
    </nav>
    
    <div class="container">
        <h1>📊 Dashboard</h1>
        <p class="subtitle">Welcome, <?= htmlspecialchars($_SESSION['admin_username'] ?? 'Admin') ?>!</p>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Quick Stats -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-label">👥 Total Teachers</div>
                <div class="stat-value"><?= number_format($total_teachers) ?></div>
                <div class="stat-sub">Active evaluators</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">📋 Questions</div>
                <div class="stat-value"><?= number_format($total_questions) ?></div>
                <div class="stat-sub">In evaluation</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">✅ Evaluations</div>
                <div class="stat-value"><?= number_format($total_evaluations) ?></div>
                <div class="stat-sub">Submitted</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">📈 Completion</div>
                <div class="stat-value"><?= number_format($completion_rate, 1) ?>%</div>
                <div class="progress-bar">
                    <div class="progress-fill" style="--progress: <?= min($completion_rate, 100) ?>%"></div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="card">
            <div class="card-header">
                📝 Recent Evaluations (Last 5)
            </div>
            <div class="card-body">
                <?php if (empty($recent)): ?>
                <p style="color: #6b7280;">No evaluations yet.</p>
                <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $eval): ?>
                        <tr>
                            <td>
                                <?php 
                                    if (isset($eval['submitted_at'])) {
                                        echo $eval['submitted_at']->toDateTime()->format('M d, h:i A');
                                    } else {
                                        echo 'Recent';
                                    }
                                ?>
                            </td>
                            <td><span style="color: #10b981;">✓ Completed</span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div style="text-align: center; margin-top: 3rem;">
            <a href="teachers.php" class="btn" style="margin-right: 1rem;">Manage Teachers</a>
            <a href="results.php" class="btn" style="background: #10b981;">View Results</a>
        </div>
    </div>
    
    <!-- MINIMAL JAVASCRIPT -->
    <script>
        // Refresh stats every 10 seconds for demo
        setTimeout(function() {
            location.reload();
        }, 10000);
    </script>
</body>
</html>
