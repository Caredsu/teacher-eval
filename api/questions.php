<?php
/**
 * Questions API Endpoint - Public & Admin
 * Public endpoint: GET /api/questions?action=get_questions (returns active questions)
 * Admin endpoint: POST to add/edit/delete (requires authentication)
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

// Define allowed statuses
const ALLOWED_STATUS = ['active', 'inactive'];

setJsonHeader();

// Debug: log the request
error_log("Questions API called - GET: " . json_encode($_GET) . ", POST: " . json_encode($_POST));

$action = getPOST('action', getGET('action', ''));

// If no action specified, default to get_questions for public access
if (empty($action)) {
    $action = 'get_questions';
}

error_log("Action: " . $action);

// Allow public access to get_questions only, require login for other actions
if ($action !== 'get_questions' && $action !== 'get_question') {
    requireLogin();
}

try {
    switch ($action) {
        case 'add_question':
            requirePermission('manage_questions');
            
            if (!verifyCSRFToken(getPOST('csrf_token'))) {
                jsonResponse(false, 'CSRF token invalid');
            }
            
            $question_text = getPOST('question_text', '');
            $category = getPOST('category', 'General');
            $question_order = (int)getPOST('question_order', 0);
            $status = getPOST('status', 'active');
            
            if (strlen($question_text) < 5) {
                jsonResponse(false, 'Question text must be at least 5 characters');
            }
            
            if (!in_array($status, ALLOWED_STATUS)) {
                jsonResponse(false, 'Invalid status');
            }
            
            $question_data = [
                'question_text' => $question_text,
                'category' => $category,
                'question_type' => 'rating',
                'question_order' => $question_order,
                'status' => $status,
                'created_at' => new UTCDateTime(),
                'created_by' => getLoggedInAdminUsername(),
                'updated_at' => new UTCDateTime(),
                'updated_by' => getLoggedInAdminUsername()
            ];
            
            $result = $questions_collection->insertOne($question_data);
            logActivity('QUESTION_ADDED', "Added question: $question_text");
            jsonResponse(true, 'Question added successfully', ['id' => (string)$result->getInsertedId()]);
            break;
            
        case 'update_question':
            requirePermission('manage_questions');
            
            if (!verifyCSRFToken(getPOST('csrf_token'))) {
                jsonResponse(false, 'CSRF token invalid');
            }
            
            $question_id = getPOST('question_id', '');
            if (!isValidObjectId($question_id)) {
                jsonResponse(false, 'Invalid question ID');
            }
            
            $question_text = getPOST('question_text', '');
            $category = getPOST('category', 'General');
            $question_order = (int)getPOST('question_order', 0);
            $status = getPOST('status', 'active');
            
            if (strlen($question_text) < 5) {
                jsonResponse(false, 'Question text must be at least 5 characters');
            }
            
            if (!in_array($status, ALLOWED_STATUS)) {
                jsonResponse(false, 'Invalid status');
            }
            
            $questions_collection->updateOne(
                ['_id' => new ObjectId($question_id)],
                ['$set' => [
                    'question_text' => $question_text,
                    'category' => $category,
                    'question_order' => $question_order,
                    'status' => $status,
                    'updated_at' => new UTCDateTime(),
                    'updated_by' => getLoggedInAdminUsername()
                ]]
            );
            
            logActivity('QUESTION_UPDATED', "Updated question: $question_text");
            jsonResponse(true, 'Question updated successfully');
            break;
            
        case 'delete_question':
            requirePermission('manage_questions');
            
            if (!verifyCSRFToken(getPOST('csrf_token'))) {
                jsonResponse(false, 'CSRF token invalid');
            }
            
            $question_id = getPOST('question_id', '');
            if (!isValidObjectId($question_id)) {
                jsonResponse(false, 'Invalid question ID');
            }
            
            $question = $questions_collection->findOne(['_id' => new ObjectId($question_id)]);
            if (!$question) {
                jsonResponse(false, 'Question not found');
            }
            
            $questions_collection->deleteOne(['_id' => new ObjectId($question_id)]);
            logActivity('QUESTION_DELETED', "Deleted question: " . ($question['question_text'] ?? ''));
            jsonResponse(true, 'Question deleted successfully');
            break;
            
        case 'toggle_status':
            requirePermission('manage_questions');
            
            if (!verifyCSRFToken(getPOST('csrf_token'))) {
                jsonResponse(false, 'CSRF token invalid');
            }
            
            $question_id = getPOST('question_id', '');
            if (!isValidObjectId($question_id)) {
                jsonResponse(false, 'Invalid question ID');
            }
            
            $question = $questions_collection->findOne(['_id' => new ObjectId($question_id)]);
            if (!$question) {
                jsonResponse(false, 'Question not found');
            }
            
            $current_status = $question['status'] ?? 'active';
            $new_status = $current_status === 'active' ? 'inactive' : 'active';
            
            $questions_collection->updateOne(
                ['_id' => new ObjectId($question_id)],
                ['$set' => [
                    'status' => $new_status,
                    'updated_at' => new UTCDateTime()
                ]]
            );
            
            jsonResponse(true, 'Status toggled', ['new_status' => $new_status]);
            break;
            
        case 'get_questions':
            // Filter only ACTIVE questions, sorted by question_order
            $questions = $questions_collection->find(
                ['status' => 'active'],  // Only active questions
                ['sort' => ['question_order' => 1, 'created_at' => -1]]
            )->toArray();
            
            // Format questions for JSON response (convert BSON objects to strings)
            $formatted_questions = [];
            foreach ($questions as $question) {
                $formatted_questions[] = [
                    'id' => (string)$question['_id'],
                    'question_text' => $question['question_text'] ?? '',
                    'category' => $question['category'] ?? 'General',
                    'question_order' => $question['question_order'] ?? 0,
                    'question_type' => $question['question_type'] ?? 'rating',
                    'status' => $question['status'] ?? 'active'
                ];
            }
            
            jsonResponse(true, 'Success', $formatted_questions);
            break;
            
        case 'get_question':
            $question_id = getGET('id', getPOST('question_id', ''));
            if (!isValidObjectId($question_id)) {
                jsonResponse(false, 'Invalid question ID');
            }
            
            $question = $questions_collection->findOne(['_id' => new ObjectId($question_id)]);
            if (!$question) {
                jsonResponse(false, 'Question not found');
            }
            
            // Format single question for JSON response
            $formatted_question = [
                'id' => (string)$question['_id'],
                'question_text' => $question['question_text'] ?? '',
                'category' => $question['category'] ?? 'General',
                'question_order' => $question['question_order'] ?? 0,
                'question_type' => $question['question_type'] ?? 'rating',
                'status' => $question['status'] ?? 'active'
            ];
            
            jsonResponse(true, 'Success', $formatted_question);
            break;
            
        default:
            jsonResponse(false, 'Invalid action');
    }
    
} catch (\Exception $e) {
    logActivity('ERROR', 'Questions API error: ' . $e->getMessage());
    jsonResponse(false, 'Database error: ' . $e->getMessage(), null, 500);
}
