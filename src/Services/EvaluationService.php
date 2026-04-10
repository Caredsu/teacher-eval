<?php
/**
 * Evaluation Service - Process Evaluations and Calculate Results
 */

namespace App\Services;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

class EvaluationService
{
    private $repository;
    
    public function __construct($repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * Get all evaluations for teacher
     */
    public function getByTeacher($teacherId, $page = 1, $limit = 10)
    {
        $skip = ($page - 1) * $limit;
        $evaluations = $this->repository->getByTeacher($teacherId, $limit, $skip);
        $total = $this->repository->countByTeacher($teacherId);
        
        return [
            'data' => $evaluations,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    /**
     * Submit evaluation
     */
    public function submit($data)
    {
        if (empty($data['teacher_id'])) {
            throw new ValidationException('Teacher ID is required');
        }
        
        if (empty($data['answers'])) {
            throw new ValidationException('Answers are required');
        }
        
        // Detect academic year and semester
        $now = new \DateTime('now', new \DateTimeZone('Asia/Manila'));
        $currentMonth = (int)$now->format('m');
        $currentYear = (int)$now->format('Y');
        
        if ($currentMonth >= 7) {
            $academicYear = $currentYear . '-' . ($currentYear + 1);
        } else {
            $academicYear = ($currentYear - 1) . '-' . $currentYear;
        }
        
        $semester = ($currentMonth >= 1 && $currentMonth <= 6) ? 1 : 2;
        $period = $academicYear . '-SEM' . $semester;
        
        $evaluationData = [
            'teacher_id' => new ObjectId($data['teacher_id']),
            'answers' => $data['answers'],
            'feedback' => $data['feedback'] ?? '',
            'academic_year' => $academicYear,
            'semester' => $semester,
            'period' => $period,
            'ip_address' => $data['ip_address'] ?? '127.0.0.1',
            'user_agent' => $data['user_agent'] ?? '',
            'submitted_at' => new UTCDateTime(),
            'created_at' => new UTCDateTime()
        ];
        
        $result = $this->repository->create($evaluationData);
        
        return [
            'id' => (string)$result,
            'teacher_id' => $data['teacher_id'],
            'submitted_at' => $now->format('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Get evaluation statistics
     */
    public function getStatistics($teacherId)
    {
        $evaluations = $this->repository->getByTeacher($teacherId);
        
        if (empty($evaluations)) {
            return [
                'total' => 0,
                'average_rating' => 0,
                'by_semester' => []
            ];
        }
        
        $total = count($evaluations);
        $ratings = [];
        $bySemester = [];
        
        foreach ($evaluations as $eval) {
            if (isset($eval['answers'])) {
                foreach ($eval['answers'] as $answer) {
                    if (isset($answer['rating'])) {
                        $ratings[] = $answer['rating'];
                    }
                }
            }
            
            $period = $eval['period'] ?? 'unknown';
            if (!isset($bySemester[$period])) {
                $bySemester[$period] = 0;
            }
            $bySemester[$period]++;
        }
        
        $avgRating = !empty($ratings) ? array_sum($ratings) / count($ratings) : 0;
        
        return [
            'total' => $total,
            'average_rating' => number_format($avgRating, 2),
            'by_semester' => $bySemester
        ];
    }
    
    /**
     * Format evaluation for response
     */
    private function formatEvaluation($evaluation)
    {
        return [
            'id' => (string)$evaluation['_id'],
            'teacher_id' => (string)$evaluation['teacher_id'],
            'answers' => $evaluation['answers'] ?? [],
            'feedback' => $evaluation['feedback'] ?? '',
            'period' => $evaluation['period'] ?? '',
            'submitted_at' => $evaluation['submitted_at'] ? $evaluation['submitted_at']->toDateTime()->format('Y-m-d H:i:s') : ''
        ];
    }
}
?>
