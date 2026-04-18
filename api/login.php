<?php
/**
 * Login API Endpoint
 * POST /api/login
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

if ($method !== 'POST') {
    sendError('Method not allowed', 405);
}

// Get JSON body
$body = getJsonBody();

if (!$body) {
    sendError('Invalid JSON body', 400);
}

// Validate required fields
$validation = validateRequiredFields($body, ['username', 'password']);
if (!$validation['valid']) {
    sendError($validation['message'], 400);
}

$username = sanitizeInput($body['username']);
$password = $body['password'];

try {
    $db = Database::getInstance();
    $usersCollection = $db->getCollection('users');

    // Find user by username
    $user = $usersCollection->findOne(['username' => $username]);

    if (!$user) {
        sendError('Invalid username or password', 401);
    }

    // Verify password
    if (!verifyPassword($password, $user['password_hashed'])) {
        sendError('Invalid username or password', 401);
    }

    // Check if user account is active
    $user_status = $user['status'] ?? 'active';
    if ($user_status !== 'active') {
        sendError('Your account has been deactivated. Please contact an administrator.', 403);
    }

    // Check if role is admin (superadmin or staff)
    if (!in_array($user['role'], ['superadmin', 'staff'])) {
        sendError('Access denied for your role', 403);
    }

    // Generate token
    $token = generateToken($user['_id'], $user['role']);

    // Return success response
    sendSuccess([
        'token' => $token,
        'user' => [
            'id' => objectIdToString($user['_id']),
            'username' => $user['username'],
            'role' => $user['role']
        ]
    ], 'Login successful', 200);

} catch (\Exception $e) {
    sendError('Database error: ' . $e->getMessage(), 500);
}
