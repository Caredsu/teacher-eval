<?php
/**
 * Teacher Evaluation System - Main Router
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/helpers.php';

// Set response headers (including CORS)
setJsonHeader();

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Get the request path
// First check if there's a 'request' query parameter from .htaccess rewrite
if (isset($_GET['request'])) {
    $request = $_GET['request'];
} else {
    // Fallback to parsing the URI path
    $request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $request = str_replace('/teacher-eval', '', $request);
    $request = trim($request, '/');
    
    // Remove index.php if it's in the path
    $request = str_replace('index.php', '', $request);
    $request = trim($request, '/');
}

// Route the request
if (strpos($request, 'api/') === 0) {
    $path = substr($request, 4); // Remove 'api/' prefix

    // Extract the endpoint
    $endpoint = explode('/', $path)[0];

    // Route to appropriate API file
    switch ($endpoint) {
        case 'login':
            require_once __DIR__ . '/api/login.php';
            break;
        case 'teachers':
            require_once __DIR__ . '/api/teachers.php';
            break;
        case 'questions':
            require_once __DIR__ . '/api/questions.php';
            break;
        case 'evaluations':
            require_once __DIR__ . '/api/evaluations.php';
            break;
        case 'departments':
            require_once __DIR__ . '/api/departments.php';
            break;
        default:
            sendError('Endpoint not found', 404);
    }
} elseif ($request === 'api' || $request === '') {
    // Handle base API path - return health check
    sendSuccess([
        'status' => 'ok',
        'version' => '1.0.0',
        'timestamp' => date('Y-m-d H:i:s')
    ], 'API is running', 200);
} else {
    // Show API documentation
    showDocumentation();
}

function showDocumentation() {
    setJsonHeader();
    echo json_encode([
        'name' => 'Teacher Evaluation System API',
        'version' => '1.0.0',
        'endpoints' => [
            [
                'method' => 'POST',
                'path' => '/api/login',
                'description' => 'Admin login (returns JWT token)',
                'auth' => false,
                'body' => ['username' => 'string', 'password' => 'string'],
                'response' => ['token' => 'JWT', 'user' => 'object']
            ],
            [
                'method' => 'GET',
                'path' => '/api/teachers',
                'description' => 'Get all teachers',
                'auth' => true,
                'roles' => ['superadmin', 'staff'],
                'response' => ['id' => 'string', 'firstname' => 'string', 'lastname' => 'string', 'department' => 'string']
            ],
            [
                'method' => 'POST',
                'path' => '/api/teachers',
                'description' => 'Add new teacher',
                'auth' => true,
                'roles' => ['superadmin'],
                'body' => ['firstname' => 'string', 'lastname' => 'string', 'middlename' => 'string', 'department' => 'ECT|EDUC|CCJE|BHT'],
                'response' => ['id' => 'string', 'firstname' => 'string']
            ],
            [
                'method' => 'PUT',
                'path' => '/api/teachers/:id',
                'description' => 'Update teacher',
                'auth' => true,
                'roles' => ['superadmin'],
                'body' => ['firstname' => 'string (optional)', 'lastname' => 'string (optional)', 'department' => 'string (optional)']
            ],
            [
                'method' => 'DELETE',
                'path' => '/api/teachers/:id',
                'description' => 'Delete teacher',
                'auth' => true,
                'roles' => ['superadmin']
            ],
            [
                'method' => 'POST',
                'path' => '/api/evaluations',
                'description' => 'Submit evaluation (anonymous)',
                'auth' => false,
                'body' => [
                    'teacher_id' => 'string',
                    'ratings' => [
                        'teaching' => '1-5',
                        'communication' => '1-5',
                        'knowledge' => '1-5'
                    ],
                    'feedback' => 'string (10-1000 chars)'
                ]
            ],
            [
                'method' => 'GET',
                'path' => '/api/evaluations/:teacher_id',
                'description' => 'Get evaluations for a teacher',
                'auth' => true,
                'roles' => ['superadmin', 'staff'],
                'response' => [
                    'teacher' => 'object',
                    'statistics' => ['total' => 'int', 'average_teaching' => 'float', 'average_communication' => 'float', 'average_knowledge' => 'float'],
                    'evaluations' => 'array'
                ]
            ],
            [
                'method' => 'GET',
                'path' => '/api/departments',
                'description' => 'List all departments',
                'auth' => false,
                'response' => ['code' => 'string', 'name' => 'string']
            ],
            [
                'method' => 'GET',
                'path' => '/api/questions?action=get_questions',
                'description' => 'Get all active questions for evaluation',
                'auth' => false,
                'response' => ['id' => 'string', 'question_text' => 'string', 'category' => 'string', 'question_type' => 'string', 'question_order' => 'int', 'status' => 'string']
            ]
        ]
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
