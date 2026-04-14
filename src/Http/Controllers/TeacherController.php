<?php
/**
 * Teacher Controller - Teacher Management API
 */

namespace App\Http\Controllers;

use App\Core\Response;
use App\Core\Request;
use App\Services\TeacherService;
use App\Repositories\TeacherRepository;
use App\Validators\TeacherValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

class TeacherController
{
    private $service;
    private $request;
    private $db;
    private $validator;
    
    public function __construct($db, Request $request)
    {
        $this->db = $db;
        $this->request = $request;
        $this->validator = new TeacherValidator();
        
        $repository = new TeacherRepository($db);
        $this->service = new TeacherService($repository);
    }
    
    /**
     * GET /api/teachers - Get all teachers
     */
    public function index()
    {
        try {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 10);
            
            $result = $this->service->all($page, $limit);
            return Response::success($result);
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
    
    /**
     * GET /api/teachers/:id - Get single teacher
     */
    public function show($id)
    {
        try {
            $teacher = $this->service->find($id);
            return Response::success($teacher);
        } catch (NotFoundException $e) {
            return Response::notFound($e->getMessage());
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
    
    /**
     * POST /api/teachers - Create teacher
     */
    public function store()
    {
        try {
            $data = $this->request->all();
            
            // Validate request
            $this->validator->validateCreate($data);
            if ($this->validator->fails()) {
                return Response::validation($this->validator->errors());
            }
            
            $teacher = $this->service->create($data);
            return Response::created($teacher);
        } catch (ValidationException $e) {
            return Response::validation(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
    
    /**
     * PUT /api/teachers/:id - Update teacher
     */
    public function update($id)
    {
        try {
            $data = $this->request->all();
            
            // Validate request
            $this->validator->validateUpdate($data);
            if ($this->validator->fails()) {
                return Response::validation($this->validator->errors());
            }
            
            // Get current user ID/username from session
            $userId = $_SESSION['admin_username'] ?? $_SESSION['admin_id'] ?? 'system';
            $teacher = $this->service->update($id, $data, $userId);
            return Response::success($teacher);
        } catch (ValidationException $e) {
            return Response::validation(['error' => $e->getMessage()]);
        } catch (NotFoundException $e) {
            return Response::notFound($e->getMessage());
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
    
    /**
     * DELETE /api/teachers/:id - Delete teacher
     */
    public function destroy($id)
    {
        try {
            $this->service->delete($id);
            return Response::success(['deleted' => true]);
        } catch (NotFoundException $e) {
            return Response::notFound($e->getMessage());
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
}
?>
