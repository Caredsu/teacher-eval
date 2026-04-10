<?php
/**
 * Report Service - Generate Reports and Analytics
 */

namespace App\Services;

use MongoDB\BSON\ObjectId;

class ReportService
{
    private $evaluationRepository;
    private $teacherRepository;
    private $questionRepository;
    
    public function __construct($evaluationRepository, $teacherRepository, $questionRepository)
    {
        $this->evaluationRepository = $evaluationRepository;
        $this->teacherRepository = $teacherRepository;
        $this->questionRepository = $questionRepository;
    }
    
    /**
     * Get dashboard statistics
     */
    public function getDashboardStats()
    {
        $totalTeachers = $this->teacherRepository->count();
        $totalEvaluations = $this->evaluationRepository->count();
        
        return [
            'total_teachers' => $totalTeachers,
            'total_evaluations' => $totalEvaluations,
            'pending_evaluations' => max(0, $totalTeachers - $totalEvaluations),
            'completion_rate' => $totalTeachers > 0 ? round(($totalEvaluations / $totalTeachers) * 100, 2) : 0
        ];
    }
    
    /**
     * Get teacher ratings summary
     */
    public function getTeacherSummary($teacherId, $limit = 10)
    {
        $evaluations = $this->evaluationRepository->getByTeacher($teacherId, $limit);
        
        $totalRating = 0;
        $ratingCount = 0;
        $feedbackList = [];
        
        foreach ($evaluations as $eval) {
            if (isset($eval['answers']) && is_array($eval['answers'])) {
                foreach ($eval['answers'] as $answer) {
                    if (isset($answer['rating'])) {
                        $totalRating += $answer['rating'];
                        $ratingCount++;
                    }
                }
            }
            
            if (!empty($eval['feedback'])) {
                $feedbackList[] = [
                    'feedback' => $eval['feedback'],
                    'submitted_at' => $eval['submitted_at'] ? $eval['submitted_at']->toDateTime()->format('Y-m-d H:i:s') : ''
                ];
            }
        }
        
        $avgRating = $ratingCount > 0 ? $totalRating / $ratingCount : 0;
        
        return [
            'average_rating' => number_format($avgRating, 2),
            'total_evaluations' => count($evaluations),
            'recent_feedback' => array_slice($feedbackList, 0, 5)
        ];
    }
    
    /**
     * Get all teachers with ratings
     */
    public function getAllTeachersWithRatings()
    {
        $teachers = $this->teacherRepository->getAll();
        
        $result = [];
        foreach ($teachers as $teacher) {
            $stats = $this->getTeacherSummary((string)$teacher['_id']);
            
            $result[] = [
                'id' => (string)$teacher['_id'],
                'name' => $teacher['name'] ?? '',
                'department' => $teacher['department'] ?? '',
                'average_rating' => $stats['average_rating'],
                'evaluation_count' => $stats['total_evaluations']
            ];
        }
        
        return $result;
    }
    
    /**
     * Export evaluations data
     */
    public function exportEvaluations($filters = [])
    {
        $evaluations = $this->evaluationRepository->getAll();
        
        $data = [];
        foreach ($evaluations as $eval) {
            $data[] = [
                'teacher_id' => (string)$eval['teacher_id'],
                'submitted_at' => $eval['submitted_at'] ? $eval['submitted_at']->toDateTime()->format('Y-m-d H:i:s') : '',
                'feedback' => $eval['feedback'] ?? '',
                'answers_count' => count($eval['answers'] ?? []),
                'period' => $eval['period'] ?? ''
            ];
        }
        
        return $data;
    }
}
?>
