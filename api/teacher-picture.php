<?php
/**
 * Serve teacher picture directly
 * GET /api/teacher-picture.php?id=<teacher_id>
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';

use MongoDB\BSON\ObjectId;

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$teacherId = $_GET['id'] ?? '';

if (empty($teacherId)) {
    http_response_code(400);
    echo 'Missing teacher ID';
    exit;
}

try {
    $teachers_collection = $db->selectCollection('teachers');
    
    // Try to create ObjectId, if it fails, try as string
    try {
        $query = ['_id' => new ObjectId($teacherId)];
    } catch (\Exception $e) {
        $query = ['_id' => $teacherId];
    }
    
    $teacher = $teachers_collection->findOne($query);
    
    if (!$teacher || !isset($teacher['picture'])) {
        http_response_code(404);
        echo 'Picture not found';
        exit;
    }
    
    $picData = $teacher['picture'];
    
    // Extract base64 data if it's a data URL
    if (strpos($picData, ',') !== false) {
        list($header, $data) = explode(',', $picData, 2); 
        // Extract MIME type from header: "data:image/jpeg;base64"
        preg_match('/data:(image\/[^;]+)/', $header, $matches);
        $mimeType = $matches[1] ?? 'image/jpeg';
        $base64Data = $data;
    } else {
        $mimeType = 'image/jpeg';
        $base64Data = $picData;
    }
    
    // Decode and serve
    $imageData = base64_decode($base64Data, true);
    
    if ($imageData === false) {
        http_response_code(400);
        echo 'Invalid base64 data';
        exit;
    }
    
    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . strlen($imageData));
    header('Cache-Control: public, max-age=86400');
    echo $imageData;
    
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
