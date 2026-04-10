<?php
/**
 * Dashboard Controller - Analytics & Reports
 */

namespace App\Http\Controllers;

use App\Core\Response;
use App\Core\Request;
use App\Services\ReportService;
use App\Repositories\EvaluationRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\QuestionRepository;

class DashboardController
{
    private $reportService;
    private $request;
    
    public function __construct($db, Request $request)
    {
        $this->request = $request;
        
        $evaluationRepository = new EvaluationRepository($db);
        $teacherRepository = new TeacherRepository($db);
        $questionRepository = new QuestionRepository($db);
        
        $this->reportService = new ReportService(
            $evaluationRepository,
            $teacherRepository,
            $questionRepository
        );
    }
    
    /**
     * GET /api/dashboard/stats - Get summary statistics
     */
    public function getStats()
    {
        try {
            $stats = $this->reportService->getDashboardStats();
            return Response::success($stats);
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
    
    /**
     * GET /api/dashboard/teachers - Get all teachers with ratings
     */
    public function getTeachers()
    {
        try {
            $teachers = $this->reportService->getAllTeachersWithRatings();
            return Response::success($teachers);
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
?>
