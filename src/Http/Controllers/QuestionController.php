<?php
/**
 * Question Controller - Question Management API
 */

namespace App\Http\Controllers;

use App\Core\Response;
use App\Core\Request;
use App\Services\QuestionService;
use App\Repositories\QuestionRepository;
use App\Validators\QuestionValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

class QuestionController
{
    private $service;
    private $request;
    private $db;
    private $validator;
    
    public function __construct($db, Request $request)
    {
        $this->db = $db;
        $this->request = $request;
        $this->validator = new QuestionValidator();
        
        $repository = new QuestionRepository($db);
        $this->service = new QuestionService($repository);
    }
    
    /**
     * GET /api/questions - Get all questions
     */
    public function index()
    {
        try {
            $questions = $this->service->all();
            return Response::success($questions);
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
    
    /**
     * GET /api/questions/:id - Get single question
     */
    public function show($id)
    {
        try {
            $question = $this->service->find($id);
            return Response::success($question);
        } catch (NotFoundException $e) {
            return Response::notFound($e->getMessage());
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
    
    /**
     * POST /api/questions - Create question
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
            
            $question = $this->service->create($data);
            return Response::created($question);
        } catch (ValidationException $e) {
            return Response::validation(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
    
    /**
     * PUT /api/questions/:id - Update question
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
            
            $question = $this->service->update($id, $data);
            return Response::success($question);
        } catch (ValidationException $e) {
            return Response::validation(['error' => $e->getMessage()]);
        } catch (NotFoundException $e) {
            return Response::notFound($e->getMessage());
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
    
    /**
     * DELETE /api/questions/:id - Delete question
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
