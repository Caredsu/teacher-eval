<?php
/**
 * User Controller - Admin User Management API
 */

namespace App\Http\Controllers;

use App\Core\Response;
use App\Core\Request;
use App\Services\UserService;
use App\Repositories\UserRepository;
use App\Validators\UserValidator;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

class UserController
{
    private $service;
    private $request;
    private $db;
    private $validator;
    
    public function __construct($db, Request $request)
    {
        $this->db = $db;
        $this->request = $request;
        $this->validator = new UserValidator();
        
        // Setup service with repository
        $repository = new UserRepository($db);
        $this->service = new UserService($repository);
    }
    
    /**
     * GET /api/users - Get all users
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
     * GET /api/users/:id - Get single user
     */
    public function show($id)
    {
        try {
            $user = $this->service->find($id);
            return Response::success($user);
        } catch (NotFoundException $e) {
            return Response::notFound($e->getMessage());
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
    
    /**
     * POST /api/users - Create user
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
            
            $createdBy = $_SESSION['admin_username'] ?? 'system';
            
            $user = $this->service->create($data, $createdBy);
            return Response::created($user);
        } catch (ValidationException $e) {
            return Response::validation(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
    
    /**
     * PUT /api/users/:id - Update user
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
            
            $updatedBy = $_SESSION['admin_username'] ?? 'system';
            
            $user = $this->service->update($id, $data, $updatedBy);
            return Response::success($user);
        } catch (ValidationException $e) {
            return Response::validation(['error' => $e->getMessage()]);
        } catch (NotFoundException $e) {
            return Response::notFound($e->getMessage());
        } catch (\Exception $e) {
            return Response::error($e->getMessage());
        }
    }
    
    /**
     * DELETE /api/users/:id - Delete user
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
