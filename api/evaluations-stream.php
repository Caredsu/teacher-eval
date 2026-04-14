<?php
/**
 * Server-Sent Events (SSE) Stream for Real-time Evaluations
 * GET /api/evaluations-stream.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

// Set headers for SSE
header('Content-Type: text/event-stream;charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Connection: keep-alive');
header('Access-Control-Allow-Origin: *');
header('X-Accel-Buffering: no');

// Disable all compression and buffering
if (function_exists('apache_setenv')) {
    apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('max_execution_time', 300);

ob_end_clean();
ob_implicit_flush(true);

initializeSession();

try {
    $evaluations_collection = $db->selectCollection('evaluations');
    
    // Send initial connection message
    echo ": Connection established\n\n";
    flush();
    
    $endTime = time() + 300; // 5 minutes
    $lastTimestamp = time() - 10; // Check last 10 seconds
    
    while (time() < $endTime) {
        $currentTime = time();
        
        // Check for new evaluations from last check
        $fiveSecondsAgo = new MongoDB\BSON\UTCDateTime($lastTimestamp * 1000);
        $now = new MongoDB\BSON\UTCDateTime($currentTime * 1000);
        
        $recentEvals = $evaluations_collection->find([
            'created_at' => ['$gte' => $fiveSecondsAgo, '$lte' => $now]
        ])->toArray();
        
        if (count($recentEvals) > 0) {
            foreach ($recentEvals as $eval) {
                $ratings = $eval['ratings'] ?? [];
                $avg_rating = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 2) : 0;
                
                $eventData = [
                    'id' => (string)$eval['_id'],
                    'teacher' => 'New Evaluation',
                    'rating' => $avg_rating
                ];
                
                echo "id: " . time() . "\n";
                echo "event: new_evaluation\n";
                echo "data: " . json_encode($eventData) . "\n\n";
                flush();
            }
        }
        
        $lastTimestamp = $currentTime;
        
        // Send heartbeat every 30 seconds
        sleep(5);
    }
    
    echo "data: {\"status\":\"connection_closed\"}\n\n";
    flush();
    
} catch (\Exception $e) {
    echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
    flush();
}

exit;
?>

