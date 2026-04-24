<?php
/**
 * Admin - Submission Logs Monitor
 * View duplicate prevention logs and spam patterns
 * Requires: admin/superadmin role
 */

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../config/database.php';

// Check authentication
$user = requireAuth(['superadmin', 'staff']);

// Get filter parameters
$filter = $_GET['filter'] ?? 'all';  // all, pending, completed, spam
$teacher_id = $_GET['teacher_id'] ?? '';
$days = (int)($_GET['days'] ?? 7);

// Build query
$query = [];
$daysAgo = new \MongoDB\BSON\UTCDateTime((time() - $days * 86400) * 1000);
$query['submitted_at'] = ['$gte' => $daysAgo];

if ($filter === 'pending') {
    $query['status'] = 'pending';
} elseif ($filter === 'completed') {
    $query['status'] = 'completed';
} elseif ($filter === 'spam') {
    // IPs with more than 5 submissions in this period
    $query['ip_spam_flag'] = true;
}

if ($teacher_id) {
    $query['teacher_id'] = new \MongoDB\BSON\ObjectId($teacher_id);
}

// Get submission logs
$submissionLogs = $db->selectCollection('submission_logs');
$logs = $submissionLogs->find($query, [
    'sort' => ['submitted_at' => -1],
    'limit' => 100
])->toArray();

// Get statistics
$stats = [];
$stats['total'] = $submissionLogs->countDocuments(['submitted_at' => ['$gte' => $daysAgo]]);
$stats['completed'] = $submissionLogs->countDocuments(['status' => 'completed', 'submitted_at' => ['$gte' => $daysAgo]]);
$stats['pending'] = $submissionLogs->countDocuments(['status' => 'pending', 'submitted_at' => ['$gte' => $daysAgo]]);

// Detect spam IPs
$spamIPs = $submissionLogs->aggregate([
    ['$match' => ['submitted_at' => ['$gte' => $daysAgo], 'status' => 'completed']],
    ['$group' => [
        '_id' => '$ip_address',
        'count' => ['$sum' => 1],
        'devices' => ['$addToSet' => '$device_fingerprint'],
        'teachers' => ['$addToSet' => '$teacher_id']
    ]],
    ['$match' => ['count' => ['$gte' => 5]]],
    ['$sort' => ['count' => -1]],
    ['$limit' => 20]
])->toArray();

$stats['spam_ips'] = count($spamIPs);

// Get teachers list for dropdown
$teachers = $teachers_collection->find([], ['sort' => ['full_name' => 1]])->toArray();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission Logs - Teacher Evaluation System</title>
    <link rel="stylesheet" href="<?php echo assetPath('css/admin.css'); ?>">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            border-left: 4px solid #007bff;
        }
        .stat-card.spam {
            border-left-color: #dc3545;
        }
        .stat-card.pending {
            border-left-color: #ffc107;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
        }
        .stat-label {
            font-size: 0.875rem;
            color: #666;
            margin-top: 0.5rem;
        }
        
        .filters {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-width: 150px;
        }
        
        .logs-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .logs-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .logs-table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 1px solid #dee2e6;
        }
        
        .logs-table td {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #dee2e6;
        }
        
        .logs-table tr:hover {
            background: #f9f9f9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-badge.completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .code {
            font-family: monospace;
            font-size: 0.85rem;
            background: #f5f5f5;
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            word-break: break-all;
        }
        
        .spam-section {
            margin-top: 2rem;
        }
        
        .spam-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .empty-state {
            padding: 2rem;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1>📊 Submission Logs Monitor</h1>
    </nav>
    
    <div class="container">
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Submissions (<?php echo $days; ?> days)</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $stats['completed']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat-card pending">
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card spam">
                <div class="stat-value"><?php echo $stats['spam_ips']; ?></div>
                <div class="stat-label">Suspicious IPs</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters">
            <form method="get" style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: end; width: 100%;">
                <div class="filter-group">
                    <label>Status</label>
                    <select name="filter">
                        <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="completed" <?php echo $filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="spam" <?php echo $filter === 'spam' ? 'selected' : ''; ?>>Suspicious</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Teacher</label>
                    <select name="teacher_id">
                        <option value="">All Teachers</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?php echo (string)$t['_id']; ?>" <?php echo (string)$t['_id'] === $teacher_id ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['full_name'] ?? 'Unknown'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Days</label>
                    <select name="days">
                        <option value="1" <?php echo $days === 1 ? 'selected' : ''; ?>>Last 24 hours</option>
                        <option value="7" <?php echo $days === 7 ? 'selected' : ''; ?>>Last 7 days</option>
                        <option value="30" <?php echo $days === 30 ? 'selected' : ''; ?>>Last 30 days</option>
                        <option value="90" <?php echo $days === 90 ? 'selected' : ''; ?>>Last 90 days</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Filter</button>
            </form>
        </div>
        
        <!-- Submission Logs Table -->
        <div class="logs-table">
            <?php if (count($logs) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Teacher</th>
                            <th>Device Fingerprint</th>
                            <th>IP Address</th>
                            <th>Status</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <?php
                                $teacher = $teachers_collection->findOne(['_id' => $log['teacher_id']]);
                                $teacherName = $teacher ? htmlspecialchars($teacher['full_name'] ?? 'Unknown') : 'Unknown';
                                $timestamp = $log['submitted_at']->toDateTime()->format('Y-m-d H:i:s');
                                $fingerprint = substr($log['device_fingerprint'], 0, 16) . '...';
                            ?>
                            <tr>
                                <td><?php echo $teacherName; ?></td>
                                <td>
                                    <span class="code" title="<?php echo htmlspecialchars($log['device_fingerprint']); ?>">
                                        <?php echo $fingerprint; ?>
                                    </span>
                                </td>
                                <td><span class="code"><?php echo htmlspecialchars($log['ip_address']); ?></span></td>
                                <td>
                                    <span class="status-badge <?php echo $log['status']; ?>">
                                        <?php echo ucfirst($log['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $timestamp; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <p>No submission logs found for the selected filters.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Spam Detection Section -->
        <?php if (count($spamIPs) > 0): ?>
            <div class="spam-section">
                <h2>🚨 Suspicious IP Addresses</h2>
                <div class="spam-warning">
                    <strong>⚠️ Warning:</strong> These IP addresses have made more than 5 evaluations in the selected period.
                    This may indicate spam, bot activity, or coordinated attacks.
                </div>
                
                <div class="logs-table">
                    <table>
                        <thead>
                            <tr>
                                <th>IP Address</th>
                                <th>Submissions</th>
                                <th>Unique Devices</th>
                                <th>Teachers</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($spamIPs as $spam): ?>
                                <tr style="background: #ffe6e6;">
                                    <td><strong><?php echo htmlspecialchars($spam['_id']); ?></strong></td>
                                    <td><?php echo $spam['count']; ?></td>
                                    <td><?php echo count($spam['devices']); ?></td>
                                    <td><?php echo count($spam['teachers']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
    
    <style>
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
    </style>
</body>
</html>
