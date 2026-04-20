<?php
/**
 * Minimal test endpoint
 * Access: /teacher-eval/api/test.php
 */
header('Content-Type: application/json; charset=utf-8');

echo json_encode([
    'status' => 'API is working',
    'method' => $_SERVER['REQUEST_METHOD'],
    'uri' => $_SERVER['REQUEST_URI'],
    'query_string' => $_SERVER['QUERY_STRING'] ?? 'none',
    'timestamp' => date('Y-m-d H:i:s')
], JSON_PRETTY_PRINT);
?>
