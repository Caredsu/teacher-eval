<?php
/**
 * PDF Export API - Generates PDF exports for different report types
 */

// Start output buffering to catch any stray output
ob_start();

require_once '../includes/helpers.php';
require_once '../config/database.php';

// Clear any buffered output before setting headers
ob_clean();

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

initializeSession();

// Check authentication
if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    http_response_code(401);
    jsonResponse(false, 'Unauthorized - Please log in', []);
    exit;
}

try {
    $export_type = isset($_GET['type']) ? sanitizeInput($_GET['type']) : '';
    
    if (empty($export_type)) {
        jsonResponse(false, 'Export type not specified', []);
        exit;
    }
    
    // Test endpoint for diagnostics
    if ($export_type === 'test') {
        jsonResponse(true, 'API is working', ['test' => true]);
        exit;
    }
    
    $data = [];
    
    switch ($export_type) {
        case 'teachers':
            $data = exportTeachersData();
            break;
        case 'users':
            $data = exportUsersData();
            break;
        case 'questions':
            $data = exportQuestionsData();
            break;
        case 'results':
            $data = exportResultsData();
            break;
        case 'analytics':
            $data = exportAnalyticsData();
            break;
        case 'dashboard':
            $data = exportDashboardData();
            break;
        default:
            jsonResponse(false, 'Invalid export type', []);
            exit;
    }
    
    jsonResponse(true, 'Data exported successfully', $data);
    
} catch (\Exception $e) {
    jsonResponse(false, 'Error: ' . $e->getMessage(), []);
}

/**
 * Export Teachers Data
 */
function exportTeachersData() {
    global $teachers_collection;
    
    try {
        $teachers = $teachers_collection->find([], ['sort' => ['created_at' => -1]])->toArray();
        $rows = [];
        
        foreach ($teachers as $teacher) {
            $rows[] = [
                'First Name' => (string)($teacher['first_name'] ?? ''),
                'Middle Name' => (string)($teacher['middle_name'] ?? ''),
                'Last Name' => (string)($teacher['last_name'] ?? ''),
                'Department' => (string)($teacher['department'] ?? ''),
                'Email' => (string)($teacher['email'] ?? ''),
                'Status' => (string)($teacher['status'] ?? 'active'),
                'Date Added' => (string)formatDateTime($teacher['created_at'] ?? '')
            ];
        }
        
        return [
            'title' => 'Teachers Directory',
            'filename' => 'Teachers_' . date('Y-m-d_His') . '.pdf',
            'rows' => $rows
        ];
    } catch (\Exception $e) {
        throw new \Exception("Error exporting teachers: " . $e->getMessage());
    }
}

/**
 * Export Users Data
 */
function exportUsersData() {
    global $admins_collection;
    
    try {
        $users = $admins_collection->find([], ['sort' => ['username' => 1]])->toArray();
        $rows = [];
        
        foreach ($users as $user) {
            $rows[] = [
                'Username' => (string)($user['username'] ?? ''),
                'Role' => (string)($user['role'] ?? 'staff'),
                'Status' => isset($user['is_active']) ? ($user['is_active'] ? 'Active' : 'Inactive') : 'Active',
                'Date Created' => (string)formatDateTime($user['created_at'] ?? '')
            ];
        }
        
        return [
            'title' => 'Admin Users',
            'filename' => 'AdminUsers_' . date('Y-m-d_His') . '.pdf',
            'rows' => $rows
        ];
    } catch (\Exception $e) {
        throw new \Exception("Error exporting users: " . $e->getMessage());
    }
}

/**
 * Export Questions Data
 */
function exportQuestionsData() {
    global $questions_collection;
    
    try {
        $questions = $questions_collection->find([], ['sort' => ['question_order' => 1]])->toArray();
        $rows = [];
        
        foreach ($questions as $question) {
            $rows[] = [
                'Order' => (int)($question['question_order'] ?? 0),
                'Question' => (string)($question['question_text'] ?? ''),
                'Option A' => (string)($question['option_a'] ?? ''),
                'Option B' => (string)($question['option_b'] ?? ''),
                'Option C' => (string)($question['option_c'] ?? ''),
                'Option D' => (string)($question['option_d'] ?? ''),
                'Correct Answer' => (string)($question['correct_answer'] ?? ''),
                'Status' => isset($question['is_active']) ? ($question['is_active'] ? 'Active' : 'Inactive') : 'Active'
            ];
        }
        
        return [
            'title' => 'Evaluation Questions',
            'filename' => 'Questions_' . date('Y-m-d_His') . '.pdf',
            'rows' => $rows
        ];
    } catch (\Exception $e) {
        throw new \Exception("Error exporting questions: " . $e->getMessage());
    }
}

/**
 * Export Results Data
 */
function exportResultsData() {
    global $evaluations_collection, $teachers_collection, $questions_collection;
    
    try {
        $filter = [];
        $teacher_id = isset($_GET['teacher_id']) ? sanitizeInput($_GET['teacher_id']) : '';
        
        // Filter by teacher if specified
        if (!empty($teacher_id) && isValidObjectId($teacher_id)) {
            $filter['teacher_id'] = new MongoDB\BSON\ObjectId($teacher_id);
        }
        
        $results = $evaluations_collection->find($filter, ['sort' => ['submitted_at' => -1]])->toArray();
        
        // Get teacher info (if filtering by teacher)
        $teacher_info = null;
        if (!empty($teacher_id) && isValidObjectId($teacher_id)) {
            $teacher_info = $teachers_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($teacher_id)]);
        }
        
        $rows = [];
        $ratings = [];
        $evaluations_data = [];
        
        foreach ($results as $result) {
            // Get teacher info if not already fetched
            $teacher_name = 'N/A';
            $teacher_department = 'N/A';
            
            if (!$teacher_info && isset($result['teacher_id'])) {
                $teacher_data = $teachers_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($result['teacher_id'])]);
                if ($teacher_data) {
                    $teacher_name = trim(
                        ($teacher_data['first_name'] ?? '') . ' ' .
                        ($teacher_data['middle_name'] ?? '') . ' ' .
                        ($teacher_data['last_name'] ?? '')
                    );
                    $teacher_name = preg_replace('/\s+/', ' ', $teacher_name);
                    $teacher_department = (string)($teacher_data['department'] ?? 'N/A');
                }
            } elseif ($teacher_info) {
                $teacher_name = trim(
                    ($teacher_info['first_name'] ?? '') . ' ' .
                    ($teacher_info['middle_name'] ?? '') . ' ' .
                    ($teacher_info['last_name'] ?? '')
                );
                $teacher_name = preg_replace('/\s+/', ' ', $teacher_name);
                $teacher_department = (string)($teacher_info['department'] ?? 'N/A');
            }
            
            // Calculate average rating from answers
            $answers = $result['answers'] ?? [];
            $total_rating = 0;
            
            foreach ($answers as $answer) {
                if (isset($answer['rating'])) {
                    $total_rating += (int)$answer['rating'];
                }
            }
            
            $avg_rating = count($answers) > 0 ? round($total_rating / count($answers), 2) : 0;
            $feedback = (string)($result['feedback'] ?? '');
            $ratings[] = $avg_rating;
            
            // For individual evaluations
            $evaluations_data[] = [
                'submitted_date' => formatDateTime($result['submitted_at'] ?? ''),
                'avg_rating' => $avg_rating,
                'feedback' => $feedback,
                'details' => $answers
            ];
            
            $rows[] = [
                'Teacher' => (string)$teacher_name,
                'Department' => (string)$teacher_department,
                'Average Rating' => (float)$avg_rating,
                'Overall Feedback' => $feedback,
                'Date Submitted' => (string)formatDateTime($result['submitted_at'] ?? '')
            ];
        }
        
        // Calculate statistics if filtered by teacher
        if ($teacher_info && !empty($evaluations_data)) {
            $overall_avg = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 2) : 0;
            $highest_rating = count($ratings) > 0 ? max($ratings) : 0;
            $lowest_rating = count($ratings) > 0 ? min($ratings) : 0;
            
            $teacher_name = trim(
                ($teacher_info['first_name'] ?? '') . ' ' .
                ($teacher_info['middle_name'] ?? '') . ' ' .
                ($teacher_info['last_name'] ?? '')
            );
            $teacher_name = preg_replace('/\s+/', ' ', $teacher_name);
            
            return [
                'title' => 'Evaluation Results',
                'filename' => 'EvaluationResults_' . date('Y-m-d_His') . '.pdf',
                'teacher_name' => $teacher_name,
                'department' => (string)($teacher_info['department'] ?? 'N/A'),
                'overall_avg' => $overall_avg,
                'highest_rating' => $highest_rating,
                'lowest_rating' => $lowest_rating,
                'evaluations' => $evaluations_data,
                'rows' => $rows
            ];
        }
        
        return [
            'title' => 'Evaluation Results',
            'filename' => 'EvaluationResults_' . date('Y-m-d_His') . '.pdf',
            'rows' => $rows
        ];
    } catch (\Exception $e) {
        throw new \Exception("Error exporting results: " . $e->getMessage());
    }
}

/**
 * Export Analytics Data
 */
function exportAnalyticsData() {
    global $evaluations_collection, $teachers_collection;
    
    try {
        $total_evaluations = $evaluations_collection->countDocuments();
        $total_teachers = $teachers_collection->countDocuments();
        
        // Calculate average ratings
        $pipeline = [
            ['$group' => [
                '_id' => '$teacher_id',
                'teacher_name' => ['$first' => '$teacher_name'],
                'teacher_department' => ['$first' => '$teacher_department'],
                'avg_rating' => ['$avg' => ['$avg' => '$rating']],
                'eval_count' => ['$sum' => 1]
            ]],
            ['$sort' => ['avg_rating' => -1]],
            ['$limit' => 50]
        ];
        
        $teacher_ratings = $evaluations_collection->aggregate($pipeline)->toArray();
        
        $rows = [];
        foreach ($teacher_ratings as $rating) {
            $rows[] = [
                'Teacher' => (string)($rating['teacher_name'] ?? 'N/A'),
                'Department' => (string)($rating['teacher_department'] ?? 'N/A'),
                'Average Rating' => isset($rating['avg_rating']) ? (float)round($rating['avg_rating'], 2) : 0,
                'Total Evaluations' => (int)($rating['eval_count'] ?? 0)
            ];
        }
        
        return [
            'title' => 'Analytics Report',
            'filename' => 'Analytics_' . date('Y-m-d_His') . '.pdf',
            'summary' => [
                'Total Teachers' => (int)$total_teachers,
                'Total Evaluations' => (int)$total_evaluations
            ],
            'rows' => $rows
        ];
    } catch (\Exception $e) {
        throw new \Exception("Error exporting analytics: " . $e->getMessage());
    }
}

/**
 * Export Dashboard Data
 */
function exportDashboardData() {
    global $teachers_collection, $questions_collection, $evaluations_collection, $admins_collection, $settings_collection;
    
    try {
        $total_teachers = $teachers_collection->countDocuments();
        $total_questions = $questions_collection->countDocuments();
        $total_evaluations = $evaluations_collection->countDocuments();
        $total_admins = $admins_collection->countDocuments();
        
        // Calculate teacher ratings
        $pipeline = [
            ['$group' => [
                '_id' => '$teacher_id',
                'teacher_name' => ['$first' => '$teacher_name'],
                'avg_rating' => ['$avg' => ['$avg' => '$rating']]
            ]],
            ['$sort' => ['avg_rating' => -1]],
            ['$limit' => 20]
        ];
        
        $teacher_ratings = $evaluations_collection->aggregate($pipeline)->toArray();
        
        // Get evaluation status
        $eval_settings = $settings_collection->findOne(['_id' => 'evaluation_settings']);
        $eval_status = $eval_settings['status'] ?? 'on';
        
        return [
            'title' => 'System Dashboard Report',
            'filename' => 'DashboardReport_' . date('Y-m-d_His') . '.pdf',
            'summary' => [
                'Total Teachers' => (int)$total_teachers,
                'Total Questions' => (int)$total_questions,
                'Total Evaluations' => (int)$total_evaluations,
                'Admin Users' => (int)$total_admins,
                'Evaluation Status' => strtoupper((string)$eval_status)
            ],
            'top_rated' => array_map(function($teacher) {
                return [
                    'teacher_name' => (string)($teacher['teacher_name'] ?? 'N/A'),
                    'avg_rating' => isset($teacher['avg_rating']) ? (float)$teacher['avg_rating'] : 0
                ];
            }, iterator_to_array($teacher_ratings))
        ];
    } catch (\Exception $e) {
        throw new \Exception("Error exporting dashboard: " . $e->getMessage());
    }
}
