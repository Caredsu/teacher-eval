<?php
/**
 * API Entry Point (Bootstrap)
 * This routes all API requests through the new architecture
 */

// Load environment
if (file_exists(__DIR__ . '/.env')) {
    $env = parse_ini_file(__DIR__ . '/.env');
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

// Load composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_DEBUG') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/storage/logs/php-errors.log');

// Set timezone
date_default_timezone_set('Asia/Manila');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Import namespaces
use App\Core\Bootstrap;
use App\Core\Request;
use App\Core\Response;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\EvaluationController;
use App\Http\Controllers\DashboardController;
use App\Services\AuthService;
use App\Exceptions\AuthException;

try {
    // Initialize bootstrap container
    $app = Bootstrap::getInstance();
    
    // Get request and database
    $request = new Request();
    $db = $app->make('db');
    
    // Parse route
    $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Remove /teacher-eval/public_api.php/ and get the remaining path
    $path = preg_replace('|^/teacher-eval/public_api\.php|', '', $request_uri);
    $path = trim($path, '/');
    $method = strtoupper($_SERVER['REQUEST_METHOD']);
    
    // Set headers
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Parse URI into segments
    $segments = explode('/', $path);
    $uri = isset($segments[0]) ? $segments[0] : '';
    $action = isset($segments[1]) ? $segments[1] : '';
    $id = isset($segments[2]) ? $segments[2] : null;
    
    // Route API requests
    if ($uri === 'api') {
        $resource = $action;
        
        // Auth routes
        if ($resource === 'auth') {
            $authService = new AuthService($db->selectCollection('admins'));
            $controller = new AuthController($authService, $request);
            
            if ($id === 'login' && $method === 'POST') {
                $controller->login();
            }
            elseif ($id === 'logout' && $method === 'POST') {
                $controller->logout();
            }
            else {
                Response::notFound('Auth endpoint not found');
            }
        }
        // User routes
        elseif ($resource === 'users') {
            $controller = new UserController($db, $request);
            
            if ($method === 'GET' && !$id) {
                $controller->index();
            }
            elseif ($method === 'GET' && $id) {
                $controller->show($id);
            }
            elseif ($method === 'POST' && !$id) {
                $controller->store();
            }
            elseif ($method === 'PUT' && $id) {
                $controller->update($id);
            }
            elseif ($method === 'DELETE' && $id) {
                $controller->destroy($id);
            }
            else {
                Response::notFound('User endpoint not found');
            }
        }
        // Teacher routes
        elseif ($resource === 'teachers') {
            $controller = new TeacherController($db, $request);
            
            if ($method === 'GET' && !$id) {
                $controller->index();
            }
            elseif ($method === 'GET' && $id) {
                $controller->show($id);
            }
            elseif ($method === 'POST' && !$id) {
                $controller->store();
            }
            elseif ($method === 'PUT' && $id) {
                $controller->update($id);
            }
            elseif ($method === 'DELETE' && $id) {
                $controller->destroy($id);
            }
            else {
                Response::notFound('Teacher endpoint not found');
            }
        }
        // Question routes
        elseif ($resource === 'questions') {
            $controller = new QuestionController($db, $request);
            
            if ($method === 'GET' && !$id) {
                $controller->index();
            }
            elseif ($method === 'GET' && $id) {
                $controller->show($id);
            }
            elseif ($method === 'POST' && !$id) {
                $controller->store();
            }
            elseif ($method === 'PUT' && $id) {
                $controller->update($id);
            }
            elseif ($method === 'DELETE' && $id) {
                $controller->destroy($id);
            }
            else {
                Response::notFound('Question endpoint not found');
            }
        }
        // Evaluation routes
        elseif ($resource === 'evaluations') {
            $controller = new EvaluationController($db, $request);
            
            if ($method === 'GET' && !$id && $action === 'evaluations') {
                $controller->index();
            }
            elseif ($method === 'POST' && !$id) {
                $controller->submit();
            }
            elseif ($method === 'GET' && $id === 'stats') {
                $controller->getStats();
            }
            elseif ($method === 'GET' && $id === 'export') {
                $controller->export();
            }
            else {
                Response::notFound('Evaluation endpoint not found');
            }
        }
        // Dashboard routes
        elseif ($resource === 'dashboard') {
            $controller = new DashboardController($db, $request);
            
            if ($id === 'stats' && $method === 'GET') {
                $controller->getStats();
            }
            elseif ($id === 'teachers' && $method === 'GET') {
                $controller->getTeachers();
            }
            else {
                Response::notFound('Dashboard endpoint not found');
            }
        }
        else {
            Response::notFound('Resource not found: ' . $resource);
        }
    }
    else {
        // Not an API request - use old system for now
        require __DIR__ . '/index.php';
    }
    
} catch (Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    Response::serverError($e->getMessage());
}
?>
