<?php
/**
 * Evaluation Controller - Evaluation Submission & Results
 */

namespace App\Http\Controllers;

use App\Core\Response;
use App\Core\Request;
use App\Services\EvaluationService;
use App\Services\ReportService;
use App\Repositories\EvaluationRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\QuestionRepository;
use App\Validators\EvaluationValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

class EvaluationController
{
    private $service;
    private $reportService;
    private $request;
    private $db;
    private $validator;
    
    public function __construct($db, Request $request)
    {
        $this->db = $db;
        $this->request = $request;
        $this->validator = new EvaluationValidator();
        
        $evaluationRepository = new EvaluationRepository($db);
        $this->service = new EvaluationService($evaluationRepository);
        
        $teacherRepository = new TeacherRepository($db);
        $questionRepository = new QuestionRepository($db);
        $this->reportService = new ReportService($evaluationRepository, $teacherRepository, $questionRepository);
    }
    
    /**
     * GET /api/evaluations - Get all evaluations
     */
    public function index()
    {
        try {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 10);
            
            $result = $this->service->all($page, $limit) ?? [];
            return Response::success($result);
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
    
    /**
     * GET /api/evaluations/teacher/:id - Get teacher evaluations
     */
    public function getTeacherEvaluations($teacherId)
    {
        try {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 10);
            
            $result = $this->service->getByTeacher($teacherId, $page, $limit);
            return Response::success($result);
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
    
    /**
     * POST /api/evaluations - Submit evaluation
     */
    public function submit()
    {
        try {
            $data = $this->request->all();
            
            // Validate request
            $this->validator->validateSubmit($data);
            if ($this->validator->fails()) {
                return Response::validation($this->validator->errors());
            }
            
            $data['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $data['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $result = $this->service->submit($data);
            return Response::created($result);
        } catch (ValidationException $e) {
            return Response::validation(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
    
    /**
     * GET /api/evaluations/stats - Get dashboard statistics
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
     * GET /api/evaluations/teacher/:id/summary - Get teacher summary
     */
    public function getTeacherSummary($teacherId)
    {
        try {
            $summary = $this->reportService->getTeacherSummary($teacherId);
            return Response::success($summary);
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
    
    /**
     * GET /api/evaluations/export - Export all evaluations
     */
    public function export()
    {
        try {
            $data = $this->reportService->exportEvaluations();
            return Response::success($data);
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
?>
