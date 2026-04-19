<?php
/**
 * Analytics Data API Endpoint
 * Returns aggregated statistics for charts
 * GET /api/analytics-data - Get all analytics data
 */

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=300'); // Cache for 5 minutes
header('Pragma: cache');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

// Check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Quick aggregation - get rating distribution and top/bottom teachers
    $pipeline = [
        [
            '$match' => [
                'submitted_at' => [
                    '$gte' => new MongoDB\BSON\UTCDateTime((time() - 7776000) * 1000) // Last 90 days
                ]
            ]
        ],
        ['$unwind' => '$answers'],
        [
            '$group' => [
                '_id' => '$teacher_id',
                'avg_rating' => ['$avg' => '$answers.rating'],
                'count' => ['$sum' => 1]
            ]
        ],
        ['$sort' => ['avg_rating' => -1]],
        ['$limit' => 500]
    ];
    
    $result = $evaluations_collection->aggregate($pipeline);
    
    $top_teachers = [];
    $bottom_teachers = [];
    $rating_counts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    
    $all_results = iterator_to_array($result);
    
    // Get top 10 and bottom 10
    $top_teachers = array_slice($all_results, 0, 10);
    $bottom_teachers = array_slice(array_reverse($all_results), 0, 10);
    
    // Calculate rating distribution
    foreach ($all_results as $stat) {
        $rating = round($stat['avg_rating']);
        if ($rating >= 1 && $rating <= 5) {
            $rating_counts[$rating]++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'top_teachers' => $top_teachers,
        'bottom_teachers' => $bottom_teachers,
        'rating_distribution' => $rating_counts,
        'total_teachers_analyzed' => count($all_results)
    ]);
    
} catch (\Exception $e) {
    error_log('Analytics API error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching analytics data',
        'error' => $e->getMessage()
    ]);
}
