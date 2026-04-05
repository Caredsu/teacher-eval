<?php
require 'config/database.php';

echo "=== CHECKING TEACHER FIELDS ===\n\n";

$teacher = $teachers_collection->findOne();

if ($teacher) {
    $teacher_array = iterator_to_array($teacher);
    echo "Fields in teacher document:\n";
    foreach ($teacher_array as $key => $value) {
        echo "  - $key: " . gettype($value) . " = " . json_encode($value) . "\n";
    }
} else {
    echo "No teachers found!";
}

?>
