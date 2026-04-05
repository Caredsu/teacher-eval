<?php
require 'config/database.php';

echo "=== RECENT EVALUATIONS (SORTED) ===\n\n";

$evals = $evaluations_collection->find([], ['sort' => ['created_at' => -1], 'limit' => 10]);

foreach ($evals as $e) {
    $date = $e['created_at'] ?? 'MISSING';
    $teacher_id = isset($e['teacher_id']) ? (string)$e['teacher_id'] : 'MISSING';
    
    if ($date instanceof MongoDB\BSON\UTCDateTime) {
        $timestamp = $date->toDateTime();
        $date_str = $timestamp->format('Y-m-d H:i:s');
    } else {
        $date_str = (string)$date;
    }
    
    echo "Eval ID: " . substr((string)$e['_id'], -6) . " | Date: $date_str | Teacher: $teacher_id\n";
}

echo "\n=== CURRENT TIME ===\n";
echo "Right now: " . date('Y-m-d H:i:s') . "\n";
?>
