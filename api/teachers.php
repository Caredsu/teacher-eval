<?php
/**
 * Teachers API Endpoint
 * GET /api/teachers - Get all teachers
 * POST /api/teachers - Add new teacher (requires superadmin)
 * PUT /api/teachers/:id - Edit teacher (requires superadmin)
 * DELETE /api/teachers/:id - Delete teacher (requires superadmin)
 */

// Start session FIRST before any headers
session_start();

// Make global request path available (set by index.php router)
global $ORIGINAL_REQUEST_PATH;

// Handle CORS preflight requests FIRST
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_ORIGIN'] ?? '*'));
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
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

// Debug: Log the request details
error_log("Teachers API: Method=$method, Path=" . ($_SERVER['REQUEST_URI'] ?? 'unknown'));

try {
    if ($method === 'GET') {
        // Check if requesting a specific teacher by ID
        // Try to get ID from query parameter first, then from REQUEST_URI as fallback
        $id = $_GET['id'] ?? '';
        
        if (!$id) {
            // Fallback: try to extract from REQUEST_URI using regex
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            if (preg_match('/\/teachers\/([a-f0-9]{24})/i', $requestUri, $matches)) {
                $id = $matches[1];
            }
        }
        
        if ($id) {
            // Get specific teacher by ID
            $objectId = stringToObjectId($id);
            if (!$objectId) {
                sendError('Invalid teacher ID format', 400);
            }
            
            try {
                $teacher = $teachers_collection->findOne(['_id' => $objectId]);
                if (!$teacher) {
                    sendError('Teacher not found', 404);
                }
                
                sendSuccess([
                    'id' => objectIdToString($teacher['_id']),
                    'first_name' => $teacher['first_name'] ?? '',
                    'last_name' => $teacher['last_name'] ?? '',
                    'middle_name' => $teacher['middle_name'] ?? '',
                    'department' => $teacher['department'] ?? '',
                    'email' => $teacher['email'] ?? '',
                    'status' => $teacher['status'] ?? 'active',
                    'picture' => $teacher['picture'] ?? null,
                    'created_at' => isset($teacher['created_at']) ? $teacher['created_at']->toDateTime()->format('Y-m-d H:i:s') : '',
                    'updated_at' => isset($teacher['updated_at']) ? $teacher['updated_at']->toDateTime()->format('Y-m-d H:i:s') : '',
                    'updated_by' => $teacher['updated_by'] ?? 'system'
                ], 'Teacher retrieved successfully', 200);
            } catch (\Exception $e) {
                sendError('Error retrieving teacher: ' . $e->getMessage(), 500);
            }
        }
        
        // Get all teachers - PUBLIC ACCESS (with field projection)
        try {
            $teachers = $teachers_collection->find([], [
                'projection' => [
                    'first_name' => 1, 'last_name' => 1, 'middle_name' => 1,
                    'department' => 1, 'email' => 1, 'status' => 1, 'picture' => 1,
                    'created_at' => 1, 'updated_at' => 1, 'updated_by' => 1
                ]
            ])->toArray();

            $formattedTeachers = array_map(function($teacher) {
                return [
                    'id' => objectIdToString($teacher['_id']),
                    'first_name' => $teacher['first_name'] ?? '',
                    'last_name' => $teacher['last_name'] ?? '',
                    'middle_name' => $teacher['middle_name'] ?? '',
                    'department' => $teacher['department'] ?? '',
                    'email' => $teacher['email'] ?? '',
                    'status' => $teacher['status'] ?? 'active',
                    'picture' => $teacher['picture'] ?? null,
                    'created_at' => isset($teacher['created_at']) ? $teacher['created_at']->toDateTime()->format('Y-m-d H:i:s') : '',
                    'updated_at' => isset($teacher['updated_at']) ? $teacher['updated_at']->toDateTime()->format('Y-m-d H:i:s') : '',
                    'updated_by' => $teacher['updated_by'] ?? 'system'
                ];
            }, $teachers);

            sendSuccess($formattedTeachers, 'Teachers retrieved successfully', 200);
        } catch (\Exception $e) {
            sendError('Error retrieving teachers: ' . $e->getMessage(), 500);
        }

    } elseif ($method === 'POST') {
        // Add new teacher - requires manage_teachers permission
        requireLogin();
        requirePermission('manage_teachers');

        $body = getJsonBody();
        if (!$body) {
            sendError('Invalid JSON body', 400);
        }

        // Validate required fields
        $validation = validateRequiredFields($body, ['first_name', 'last_name', 'department']);
        if (!$validation['valid']) {
            sendError($validation['message'], 400);
        }

        $firstName = sanitizeInput($body['first_name']);
        $middleName = sanitizeInput($body['middle_name'] ?? '');
        $lastName = sanitizeInput($body['last_name']);
        $department = sanitizeInput($body['department']);
        $email = sanitizeInput($body['email'] ?? '');
        $status = sanitizeInput($body['status'] ?? 'active');

        // Validate department
        if (!isValidDepartment($department)) {
            sendError('Invalid department. Valid options: ECT, EDUC, CCJE, BHT', 400);
        }

        // Insert new teacher
        $now = new MongoDB\BSON\UTCDateTime(time() * 1000);
        $result = $teachers_collection->insertOne([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_name' => $middleName,
            'department' => $department,
            'email' => $email,
            'status' => $status,
            'picture' => $body['picture'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
            'updated_by' => $_SESSION['admin_username'] ?? $_SESSION['admin_id'] ?? 'system'
        ]);

        sendSuccess([
            'id' => objectIdToString($result->getInsertedId()),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'middle_name' => $middleName,
            'department' => $department,
            'email' => $email,
            'status' => $status,
            'picture' => $body['picture'] ?? null,
            'created_at' => $now->toDateTime()->format('Y-m-d H:i:s'),
            'updated_at' => $now->toDateTime()->format('Y-m-d H:i:s'),
            'updated_by' => $_SESSION['admin_username'] ?? $_SESSION['admin_id'] ?? 'system'
        ], 'Teacher added successfully', 201);

    } elseif ($method === 'PUT') {
        // Edit teacher - requires manage_teachers permission
        requireLogin();
        requirePermission('manage_teachers');

        // Try to get ID from query parameter first, then from REQUEST_URI as fallback
        $id = $_GET['id'] ?? '';
        
        if (!$id) {
            // Fallback: try to extract from REQUEST_URI using regex
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            if (preg_match('/\/teachers\/([a-f0-9]{24})/i', $requestUri, $matches)) {
                $id = $matches[1];
            }
        }
        
        if (!$id) {
            sendError('Teacher ID not provided', 400);
        }

        $objectId = stringToObjectId($id);
        if (!$objectId) {
            sendError('Invalid teacher ID', 400);
        }

        $body = getJsonBody();
        if (!$body) {
            sendError('Invalid JSON body', 400);
        }

        // Check if teacher exists
        $teacher = $teachers_collection->findOne(['_id' => $objectId]);
        if (!$teacher) {
            sendError('Teacher not found', 404);
        }

        // Prepare update data
        $updateData = [];
        if (isset($body['first_name'])) {
            $updateData['first_name'] = sanitizeInput($body['first_name']);
        }
        
        if (isset($body['last_name'])) {
            $updateData['last_name'] = sanitizeInput($body['last_name']);
        }
        
        if (isset($body['middle_name'])) {
            $updateData['middle_name'] = sanitizeInput($body['middle_name']);
        }
        
        if (isset($body['department'])) {
            $department = sanitizeInput($body['department']);
            if (!isValidDepartment($department)) {
                sendError('Invalid department. Valid options: ECT, EDUC, CCJE, BHT', 400);
            }
            $updateData['department'] = $department;
        }
        
        if (isset($body['email'])) {
            $updateData['email'] = sanitizeInput($body['email']);
        }
        
        if (isset($body['status'])) {
            $updateData['status'] = sanitizeInput($body['status']);
        }
        
        if (isset($body['picture'])) {
            $updateData['picture'] = $body['picture'];
        }

        if (empty($updateData)) {
            sendError('No fields to update', 400);
        }

        $updateData['updated_at'] = new MongoDB\BSON\UTCDateTime(time() * 1000);
        $updateData['updated_by'] = $_SESSION['admin_username'] ?? $_SESSION['admin_id'] ?? 'system';

        // Update teacher
        $teachers_collection->updateOne(
            ['_id' => $objectId],
            ['$set' => $updateData]
        );

        $updatedTeacher = $teachers_collection->findOne(['_id' => $objectId]);

        sendSuccess([
            'id' => objectIdToString($updatedTeacher['_id']),
            'first_name' => $updatedTeacher['first_name'] ?? '',
            'last_name' => $updatedTeacher['last_name'] ?? '',
            'middle_name' => $updatedTeacher['middle_name'] ?? '',
            'department' => $updatedTeacher['department'] ?? '',
            'email' => $updatedTeacher['email'] ?? '',
            'status' => $updatedTeacher['status'] ?? 'active'
        ], 'Teacher updated successfully', 200);

    } elseif ($method === 'DELETE') {
        // Delete teacher - requires manage_teachers permission
        requireLogin();
        requirePermission('manage_teachers');

        // Try to get ID from query parameter first, then from REQUEST_URI as fallback
        $id = $_GET['id'] ?? '';
        
        if (!$id) {
            // Fallback: try to extract from REQUEST_URI using regex
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            if (preg_match('/\/teachers\/([a-f0-9]{24})/i', $requestUri, $matches)) {
                $id = $matches[1];
            }
        }
        
        if (!$id) {
            sendError('Teacher ID not provided', 400);
        }

        $objectId = stringToObjectId($id);
        if (!$objectId) {
            sendError('Invalid teacher ID', 400);
        }

        // Check if teacher exists
        $teacher = $teachers_collection->findOne(['_id' => $objectId]);
        if (!$teacher) {
            sendError('Teacher not found', 404);
        }

        // Delete teacher
        $teachers_collection->deleteOne(['_id' => $objectId]);

        // Optional: Also delete associated evaluations
        $evaluations_collection->deleteMany(['teacher_id' => $objectId]);

        sendSuccess(null, 'Teacher deleted successfully', 200);

    } else {
        sendError('Method not allowed', 405);
    }

} catch (\Exception $e) {
    sendError('Database error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine(), 500);
}
