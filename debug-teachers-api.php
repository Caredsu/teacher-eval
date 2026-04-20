<?php
/**
 * Direct test of Teachers API
 * Access: /teacher-eval/debug-teachers-api.php
 */
header('Content-Type: application/json; charset=utf-8');

$results = [];

try {
    // Load required files
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/app/Constants.php';
    require_once __DIR__ . '/includes/helpers.php';
    require_once __DIR__ . '/config/database.php';
    
    $results['database_loaded'] = 'OK';
    
    // Test if collection exists
    if (!isset($teachers_collection)) {
        throw new Exception('Teachers collection not initialized');
    }
    
    $results['collection'] = 'OK';
    
    // Try to count teachers
    $count = $teachers_collection->countDocuments();
    $results['teacher_count'] = $count;
    
    // Try to find all teachers
    $teachers = $teachers_collection->find([])->toArray();
    $results['teachers_found'] = count($teachers);
    
    if (count($teachers) > 0) {
        $sample = (array)$teachers[0];
        $results['sample_teacher_fields'] = array_keys($sample);
    }
    
    $results['status'] = 'SUCCESS';
    
} catch (Exception $e) {
    $results['error'] = $e->getMessage();
    $results['status'] = 'FAILED';
}

echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
