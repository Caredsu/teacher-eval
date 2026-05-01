<?php
/**
 * APK Download Handler
 * Serves the Android APK file for download
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    // Define APK path
    $apkPath = __DIR__ . '/../downloads/teacher-eval.apk';
    
    // Check if APK exists
    if (!file_exists($apkPath)) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'APK file not found. Please contact the administrator.'
        ]);
        exit;
    }
    
    // Get file size
    $fileSize = filesize($apkPath);
    
    // Set headers for download
    header('Content-Type: application/vnd.android.package-archive');
    header('Content-Disposition: attachment; filename="teacher-eval.apk"');
    header('Content-Length: ' . $fileSize);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: public');
    
    // Disable output buffering
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Read and output file in chunks
    $chunkSize = 1024 * 1024; // 1MB chunks
    $handle = fopen($apkPath, 'rb');
    
    if ($handle === false) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Could not open APK file for download.'
        ]);
        exit;
    }
    
    while (!feof($handle)) {
        echo fread($handle, $chunkSize);
        flush();
    }
    
    fclose($handle);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Download error: ' . $e->getMessage()
    ]);
    exit;
}
?>
