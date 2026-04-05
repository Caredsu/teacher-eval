<?php
require 'config/database.php';

echo "=== ADDING TEACHER NAMES ===\n\n";

// Sample teacher names
$teacher_names = [
    'Maria Santos',
    'Juan Dela Cruz',
    'Rosa Garcia',
    'Pedro Rodriguez',
    'Carmen Lopez',
    'Antonio Martinez',
    'Lucia Fernandez',
    'Diego Hernandez'
];

$teachers = $teachers_collection->find();
$count = 0;

foreach ($teachers as $teacher) {
    $teacher_id = $teacher['_id'];
    $name = $teacher_names[$count % count($teacher_names)];
    
    $teachers_collection->updateOne(
        ['_id' => $teacher_id],
        ['$set' => ['name' => $name]]
    );
    
    echo "✅ Updated teacher: $name\n";
    $count++;
}

echo "\n=== ADDING EVALUATION DATES ===\n\n";

// Add created_at to evaluations
$evaluations = $evaluations_collection->find();
$now = new DateTime();
$count = 0;

foreach ($evaluations as $eval) {
    $eval_id = $eval['_id'];
    $date = clone $now;
    $date->modify("-$count minutes");
    
    $evaluations_collection->updateOne(
        ['_id' => $eval_id],
        ['$set' => ['created_at' => $date]]
    );
    
    echo "✅ Updated evaluation with date: " . $date->format('Y-m-d H:i:s') . "\n";
    $count++;
}

echo "\n✅ All done!\n";
?>
