<?php
require 'config/database.php';

echo "=== CHECKING TEACHER ID MATCHING ===\n\n";

// Get one evaluation
$eval = $evaluations_collection->findOne();

if ($eval) {
    $eval_array = iterator_to_array($eval);
    $teacher_id_eval = $eval_array['teacher_id'] ?? null;
    
    echo "Teacher ID from Evaluation:\n";
    echo json_encode($teacher_id_eval) . "\n\n";
    
    // Try to find this teacher
    $teacher = $teachers_collection->findOne(['_id' => $teacher_id_eval]);
    
    if ($teacher) {
        $teacher_array = iterator_to_array($teacher);
        echo "✅ Teacher FOUND:\n";
        echo "Name: " . ($teacher_array['name'] ?? 'No name') . "\n";
        echo "ID: " . json_encode($teacher_array['_id']) . "\n";
    } else {
        echo "❌ Teacher NOT FOUND\n";
    }
}

echo "\n=== TOP 5 RECENT EVALUATIONS WITH TEACHERS ===\n\n";

$recent = iterator_to_array($evaluations_collection->find([], ['sort' => ['submitted_at' => -1], 'limit' => 5]));

foreach ($recent as $e) {
    $eval_array = iterator_to_array($e);
    $teacher_id = $eval_array['teacher_id'] ?? null;
    $submitted = $eval_array['submitted_at'] ?? null;
    
    if ($submitted instanceof MongoDB\BSON\UTCDateTime) {
        $date = $submitted->toDateTime()->format('Y-m-d H:i:s');
    } else {
        $date = 'N/A';
    }
    
    $teacher = $teachers_collection->findOne(['_id' => $teacher_id]);
    
    if ($teacher) {
        $t_array = iterator_to_array($teacher);
        $name = $t_array['name'] ?? 'Unknown';
    } else {
        $name = '❌ NOT FOUND';
    }
    
    echo "Date: $date | Teacher: $name\n";
}

?>
