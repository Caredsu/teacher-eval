<?php
/**
 * Export Evaluations Data to CSV
 * Access via: /admin/export-evaluations.php?format=csv
 */

require_once '../includes/helpers.php';
require_once '../config/database.php';

initializeSession();
requireLogin();

// Get filter parameters
$filter_teacher = getGET('teacher_id', '');
$filter_from_date = getGET('from_date', '');
$filter_to_date = getGET('to_date', '');

// Build MongoDB filter
$mongo_filter = [];

if (!empty($filter_teacher) && isValidObjectId($filter_teacher)) {
    $mongo_filter['teacher_id'] = new MongoDB\BSON\ObjectId($filter_teacher);
}

if (!empty($filter_from_date)) {
    $from_timestamp = strtotime($filter_from_date);
    if ($from_timestamp) {
        $mongo_filter['submitted_at'] = [
            '$gte' => new MongoDB\BSON\UTCDateTime($from_timestamp * 1000)
        ];
    }
}

if (!empty($filter_to_date)) {
    $to_timestamp = strtotime($filter_to_date . ' 23:59:59');
    if ($to_timestamp) {
        if (!isset($mongo_filter['submitted_at'])) {
            $mongo_filter['submitted_at'] = [];
        }
        $mongo_filter['submitted_at']['$lte'] = new MongoDB\BSON\UTCDateTime($to_timestamp * 1000);
    }
}

// Get filtered evaluations
$evaluations = $evaluations_collection->find($mongo_filter, [
    'sort' => ['submitted_at' => -1]
])->toArray();

// Get all teachers for lookup
$teachers = $teachers_collection->find([])->toArray();
$teacher_map = [];
foreach ($teachers as $teacher) {
    $teacher_map[(string)$teacher['_id']] = formatFullName(
        (string)($teacher['first_name'] ?? ''),
        (string)($teacher['middle_name'] ?? ''),
        (string)($teacher['last_name'] ?? '')
    );
}

// Prepare CSV data
$csv_headers = ['Date', 'Teacher', 'Teaching', 'Communication', 'Knowledge', 'Average', 'Feedback'];
$csv_rows = [];

foreach ($evaluations as $eval) {
    $teacher_id = (string)$eval['teacher_id'];
    $teacher_name = $teacher_map[$teacher_id] ?? 'Unknown Teacher';
    
    $ratings = $eval['ratings'] ?? [];
    $teaching = $ratings['teaching'] ?? 0;
    $communication = $ratings['communication'] ?? 0;
    $knowledge = $ratings['knowledge'] ?? 0;
    $avg = ($teaching + $communication + $knowledge) / 3;
    
    $submitted_at = 'N/A';
    if (isset($eval['submitted_at']) && $eval['submitted_at'] instanceof \MongoDB\BSON\UTCDateTime) {
        $date = $eval['submitted_at']->toDateTime();
        $date->setTimezone(new \DateTimeZone('Asia/Manila'));
        $submitted_at = $date->format('Y-m-d H:i:s');
    }
    
    $csv_rows[] = [
        $submitted_at,
        $teacher_name,
        $teaching,
        $communication,
        $knowledge,
        round($avg, 2),
        $eval['feedback'] ?? ''
    ];
}

// Generate CSV
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="evaluations_' . date('Y-m-d_His') . '.csv"');

$output = fopen('php://output', 'w');

// Write BOM for UTF-8
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write headers
fputcsv($output, $csv_headers);

// Write data rows
foreach ($csv_rows as $row) {
    fputcsv($output, $row);
}

fclose($output);
exit;
