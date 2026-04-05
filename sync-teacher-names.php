<?php
require 'config/database.php';

echo "=== SYNCING TEACHER NAMES ===\n\n";

// Sample teacher names to use
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
    
    // Split name into first and last
    $name_parts = explode(' ', $name, 2);
    $first_name = $name_parts[0] ?? '';
    $last_name = $name_parts[1] ?? '';
    
    $teachers_collection->updateOne(
        ['_id' => $teacher_id],
        ['$set' => [
            'first_name' => $first_name,
            'last_name' => $last_name,
            'middle_name' => '',
            'name' => $name
        ]]
    );
    
    echo "✅ Updated: $first_name $last_name\n";
    $count++;
}

echo "\n✅ All teacher names synchronized!\n";
?>
