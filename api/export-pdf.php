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
    global $evaluations_collection, $teachers_collection;
    
    try {
        $results = $evaluations_collection->find([], ['sort' => ['submitted_at' => -1]])->toArray();
        $rows = [];
        
        foreach ($results as $result) {
            // Get teacher info
            $teacher_id = $result['teacher_id'] ?? null;
            $teacher_name = 'N/A';
            $teacher_department = 'N/A';
            
            if ($teacher_id) {
                $teacher = $teachers_collection->findOne(['_id' => $teacher_id]);
                if ($teacher) {
                    $teacher_name = trim(
                        ($teacher['first_name'] ?? '') . ' ' .
                        ($teacher['middle_name'] ?? '') . ' ' .
                        ($teacher['last_name'] ?? '')
                    );
                    $teacher_name = preg_replace('/\s+/', ' ', $teacher_name);
                    $teacher_department = (string)($teacher['department'] ?? 'N/A');
                }
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
            
            $rows[] = [
                'Teacher' => (string)$teacher_name,
                'Department' => (string)$teacher_department,
                'Average Rating' => (float)$avg_rating,
                'Overall Feedback' => $feedback,
                'Date Submitted' => (string)formatDateTime($result['submitted_at'] ?? '')
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
