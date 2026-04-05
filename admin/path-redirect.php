<?php
/**
 * Auto-redirect helper for incorrect paths
 * If someone accesses /admin/login.php but should use /teacher-eval/admin/login.php
 */

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

// If accessing /admin/ but files are in /teacher-eval/admin/
if (
    (strpos($scriptName, '/admin/') !== false || strpos($requestUri, '/admin/') !== false) &&
    strpos($scriptName, '/teacher-eval/') === false &&
    strpos($requestUri, '/teacher-eval/') === false
) {
    // Check if files exist in /teacher-eval/ subfolder
    if (is_dir($_SERVER['DOCUMENT_ROOT'] . '/teacher-eval/admin/')) {
        // Redirect to correct path
        $newPath = str_replace('/admin/', '/teacher-eval/admin/', $requestUri);
        header('Location: ' . $newPath);
        exit;
    }
}
