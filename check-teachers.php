<?php
/**
 * Quick check - how many teachers in database?
 */
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/app/Constants.php';
    require_once __DIR__ . '/includes/helpers.php';
    require_once __DIR__ . '/config/database.php';
    
    if (!isset($teachers_collection)) {
        throw new Exception('Teachers collection not initialized');
    }
    
    // Count total documents
    $count = $teachers_collection->countDocuments();
    
    // Get first teacher to see structure
    $sample = $teachers_collection->findOne([]);
    $sample_fields = $sample ? array_keys((array)$sample) : [];
    
    echo json_encode([
        'total_teachers' => $count,
        'sample_teacher_fields' => $sample_fields,
        'status' => 'OK'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'status' => 'FAILED'
    ], JSON_PRETTY_PRINT);
}
?>
