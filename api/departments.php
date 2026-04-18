<?php
/**
 * Departments API Endpoint
 * GET /api/departments - Get list of all departments
 */

// Handle CORS preflight requests FIRST
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

setJsonHeader();

$method = getRequestMethod();

if ($method !== 'GET') {
    sendError('Method not allowed', 405);
}

try {
    // Get departments - accessible to all (public endpoint)
    $departments = array_map(function($dept) {
        return [
            'code' => $dept,
            'name' => getDepartmentName($dept)
        ];
    }, getValidDepartments());

    sendSuccess($departments, 'Departments retrieved successfully', 200);

} catch (\Exception $e) {
    sendError('Error: ' . $e->getMessage(), 500);
}

/**
 * Get full department name from code
 */
function getDepartmentName($code) {
    $departments = [
        'ECT' => 'Education and Communication Technologies',
        'EDUC' => 'Education',
        'CCJE' => 'Criminal Justice and Education',
        'BHT' => 'Business and Hospitality'
    ];
    return $departments[$code] ?? $code;
}
