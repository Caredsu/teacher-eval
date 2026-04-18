<?php
/**
 * Auth Controller
 * Handles authentication endpoints
 */

namespace App\Http\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Services\AuthService;
use App\Exceptions\AuthException;

class AuthController
{
    private $authService;
    private $request;
    
    public function __construct(AuthService $authService, Request $request)
    {
        $this->authService = $authService;
        $this->request = $request;
    }
    
    /**
     * POST /api/auth/login
     * Login user and return user data
     */
    public function login()
    {
        try {
            // Validate input
            $errors = $this->request->validate([
                'username' => 'required|min:3',
                'password' => 'required|min:6'
            ]);
            
            if (!empty($errors)) {
                Response::validation($errors);
            }
            
            // Attempt login
            $user = $this->authService->login(
                $this->request->get('username'),
                $this->request->get('password')
            );
            
            // Set session
            session_start();
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            $_SESSION['admin_role'] = $user['role'];
            
            Response::success($user, 'Login successful');
            
        } catch (AuthException $e) {
            Response::unauthorized($e->getMessage());
        } catch (\Exception $e) {
            error_log('Login error: ' . $e->getMessage());
            Response::serverError('Login failed. Please try again.');
        }
    }
    
    /**
     * POST /api/auth/logout
     * Logout user
     */
    public function logout()
    {
        session_start();
        session_destroy();
        Response::success(null, 'Logout successful');
    }
}
?>
