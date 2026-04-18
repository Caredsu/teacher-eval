<?php
/**
 * Users API Endpoint - Super Admin Only
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

setJsonHeader();
requireLogin();

// Only super_admin can access this endpoint
if (!hasRole('super_admin')) {
    jsonResponse(false, 'Unauthorized', null, 403);
}

$action = getPOST('action', getGET('action', ''));
$db = Database::getInstance();
$adminsCollection = $db->getCollection('admins');

try {
    switch ($action) {
        case 'add_user':
            if (!verifyCSRFToken(getPOST('csrf_token'))) {
                jsonResponse(false, 'CSRF token invalid');
            }
            
            $username = getPOST('username', '');
            $email = getPOST('email', '');
            $password = getPOST('password', '');
            $role = getPOST('role', '');
            $status = getPOST('status', 'active');
            
            // Validation
            if (strlen($username) < 3) {
                jsonResponse(false, 'Username must be at least 3 characters');
            }
            
            if (!isValidEmail($email)) {
                jsonResponse(false, 'Invalid email format');
            }
            
            if (strlen($password) < 6) {
                jsonResponse(false, 'Password must be at least 6 characters');
            }
            
            if (!in_array($role, ALLOWED_ROLES)) {
                jsonResponse(false, 'Invalid role');
            }
            
            if (!in_array($status, ALLOWED_STATUS)) {
                jsonResponse(false, 'Invalid status');
            }
            
            // Check for duplicate username
            $existing = $adminsCollection->findOne(['username' => $username]);
            if ($existing) {
                jsonResponse(false, 'Username already exists');
            }
            
            $user_data = [
                'username' => $username,
                'email' => $email,
                'password' => hashPassword($password),
                'role' => $role,
                'status' => $status,
                'created_at' => new UTCDateTime(),
                'created_by' => getLoggedInAdminUsername(),
                'updated_at' => new UTCDateTime(),
                'updated_by' => getLoggedInAdminUsername(),
                'last_login' => null
            ];
            
            $result = $adminsCollection->insertOne($user_data);
            logActivity('USER_ADDED', "Added user: $username ($role)");
            jsonResponse(true, 'User added successfully', ['id' => (string)$result->getInsertedId()]);
            break;
            
        case 'update_user':
            if (!verifyCSRFToken(getPOST('csrf_token'))) {
                jsonResponse(false, 'CSRF token invalid');
            }
            
            $user_id = getPOST('user_id', '');
            if (!isValidObjectId($user_id)) {
                jsonResponse(false, 'Invalid user ID');
            }
            
            $email = getPOST('email', '');
            $role = getPOST('role', '');
            $status = getPOST('status', 'active');
            $password = getPOST('password', '');
            
            if (!isValidEmail($email)) {
                jsonResponse(false, 'Invalid email format');
            }
            
            if (!in_array($role, ALLOWED_ROLES)) {
                jsonResponse(false, 'Invalid role');
            }
            
            if (!in_array($status, ALLOWED_STATUS)) {
                jsonResponse(false, 'Invalid status');
            }
            
            $update_data = [
                'email' => $email,
                'role' => $role,
                'status' => $status,
                'updated_at' => new UTCDateTime(),
                'updated_by' => getLoggedInAdminUsername()
            ];
            
            if (!empty($password)) {
                if (strlen($password) < 6) {
                    jsonResponse(false, 'Password must be at least 6 characters');
                }
                $update_data['password'] = hashPassword($password);
            }
            
            $adminsCollection->updateOne(
                ['_id' => new ObjectId($user_id)],
                ['$set' => $update_data]
            );
            
            logActivity('USER_UPDATED', "Updated user ID: $user_id");
            jsonResponse(true, 'User updated successfully');
            break;
            
        case 'delete_user':
            if (!verifyCSRFToken(getPOST('csrf_token'))) {
                jsonResponse(false, 'CSRF token invalid');
            }
            
            $user_id = getPOST('user_id', '');
            if (!isValidObjectId($user_id)) {
                jsonResponse(false, 'Invalid user ID');
            }
            
            // Prevent deletion of current user
            if ($user_id === $_SESSION['admin_id']) {
                jsonResponse(false, 'Cannot delete your own account');
            }
            
            $user = $adminsCollection->findOne(['_id' => new ObjectId($user_id)]);
            if (!$user) {
                jsonResponse(false, 'User not found');
            }
            
            $adminsCollection->deleteOne(['_id' => new ObjectId($user_id)]);
            logActivity('USER_DELETED', "Deleted user: " . ($user['username'] ?? $user_id));
            jsonResponse(true, 'User deleted successfully');
            break;
            
        case 'get_users':
            if (!verifyCSRFToken(getPOST('csrf_token'))) {
                jsonResponse(false, 'CSRF token invalid');
            }
            
            $users = $adminsCollection->find([], ['sort' => ['created_at' => -1]])->toArray();
            
            // Format response
            $formatted_users = [];
            foreach ($users as $user) {
                $formatted_users[] = [
                    '_id' => (string)$user['_id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'role_display' => $user['role'] === 'super_admin' ? 'Super Admin' : 'Admin',
                    'status' => $user['status'],
                    'created_at' => isset($user['created_at']) ? formatDateTime($user['created_at']) : '',
                    'created_by' => $user['created_by'] ?? '',
                    'last_login' => isset($user['last_login']) ? formatDateTime($user['last_login']) : 'Never'
                ];
            }
            
            jsonResponse(true, 'Success', $formatted_users);
            break;
            
        case 'get_user':
            $user_id = getGET('id', getPOST('user_id', ''));
            if (!isValidObjectId($user_id)) {
                jsonResponse(false, 'Invalid user ID');
            }
            
            $user = $adminsCollection->findOne(['_id' => new ObjectId($user_id)]);
            if (!$user) {
                jsonResponse(false, 'User not found');
            }
            
            jsonResponse(true, 'Success', $user);
            break;
            
        default:
            jsonResponse(false, 'Invalid action');
    }
    
} catch (\Exception $e) {
    logActivity('ERROR', 'Users API error: ' . $e->getMessage());
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
