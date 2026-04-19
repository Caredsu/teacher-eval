<?php
require_once 'config/database.php';

echo "=== DATABASE DATA CHECK ===\n\n";

// Check teachers
$teacher_count = $teachers_collection->countDocuments();
echo "👥 Teachers: " . $teacher_count . " records\n";
if ($teacher_count > 0) {
    $sample = $teachers_collection->findOne();
    echo "   Sample: " . json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

// Check evaluations
$eval_count = $evaluations_collection->countDocuments();
echo "\n📊 Evaluations (total): " . $eval_count . " records\n";

// Check evaluations from last 30 days
$thirty_days_ago = new MongoDB\BSON\UTCDateTime((time() - 2592000) * 1000);
$recent_count = $evaluations_collection->countDocuments([
    'submitted_at' => ['$gte' => $thirty_days_ago]
]);
echo "📊 Evaluations (last 30 days): " . $recent_count . " records\n";

if ($eval_count > 0) {
    $sample = $evaluations_collection->findOne();
    echo "   Sample: " . json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

// Check questions
$question_count = $questions_collection->countDocuments(['status' => 'active']);
echo "\n❓ Questions (active): " . $question_count . " records\n";

// Check admins
$admin_count = $admins_collection->countDocuments();
echo "\n🔐 Admins: " . $admin_count . " records\n";

echo "\n=== SUMMARY ===\n";
if ($teacher_count === 0) {
    echo "⚠️  No teachers found - need to create test teachers\n";
}
if ($eval_count === 0) {
    echo "⚠️  No evaluations found - need to create test evaluations\n";
}
if ($question_count === 0) {
    echo "⚠️  No questions found - need to create questions\n";
}
if ($teacher_count > 0 && $eval_count > 0 && $question_count > 0) {
    echo "✅ All data looks good!\n";
    if ($recent_count === 0) {
        echo "⚠️  But: No evaluations in last 30 days - they might be older\n";
    }
}
?>
