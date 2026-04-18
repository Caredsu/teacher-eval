<?php
/**
 * User Service - User Management Business Logic
 */

namespace App\Services;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

class UserService
{
    private $repository;
    
    public function __construct($repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * Get all users with pagination
     */
    public function all($page = 1, $limit = 10)
    {
        $skip = ($page - 1) * $limit;
        $users = $this->repository->getAll($limit, $skip);
        $total = $this->repository->count();
        
        // Format all users
        $formatted = array_map(function($user) {
            return $this->formatUser($user);
        }, $users);
        
        return [
            'data' => $formatted,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    /**
     * Get single user by ID
     */
    public function find($id)
    {
        $user = $this->repository->findById($id);
        if (!$user) {
            throw new NotFoundException('User not found');
        }
        return $this->formatUser($user);
    }
    
    /**
     * Create new user
     */
    public function create($data, $createdBy)
    {
        // Validation
        if (empty($data['username']) || strlen($data['username']) < 3) {
            throw new ValidationException('Username must be at least 3 characters');
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Invalid email format');
        }
        
        if (empty($data['password']) || strlen($data['password']) < 6) {
            throw new ValidationException('Password must be at least 6 characters');
        }
        
        // Check duplicates
        if ($this->repository->findByUsername($data['username'])) {
            throw new ValidationException('Username already exists');
        }
        
        if ($this->repository->findByEmail($data['email'])) {
            throw new ValidationException('Email already exists');
        }
        
        // Create user
        $userData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'role' => $data['role'] ?? 'staff',
            'status' => $data['status'] ?? 'active',
            'created_at' => new UTCDateTime(),
            'created_by' => $createdBy,
            'updated_at' => new UTCDateTime(),
            'updated_by' => $createdBy,
            'last_login' => null
        ];
        
        $result = $this->repository->create($userData);
        
        return [
            'id' => (string)$result,
            'username' => $userData['username'],
            'email' => $userData['email'],
            'role' => $userData['role'],
            'status' => $userData['status']
        ];
    }
    
    /**
     * Update user
     */
    public function update($id, $data, $updatedBy)
    {
        $user = $this->repository->findById($id);
        if (!$user) {
            throw new NotFoundException('User not found');
        }
        
        $updateData = ['updated_at' => new UTCDateTime(), 'updated_by' => $updatedBy];
        
        if (isset($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new ValidationException('Invalid email format');
            }
            
            $existing = $this->repository->findByEmail($data['email']);
            if ($existing && (string)$existing['_id'] !== $id) {
                throw new ValidationException('Email already in use');
            }
            
            $updateData['email'] = $data['email'];
        }
        
        if (isset($data['role'])) {
            $updateData['role'] = $data['role'];
        }
        
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }
        
        if (isset($data['password']) && !empty($data['password'])) {
            if (strlen($data['password']) < 6) {
                throw new ValidationException('Password must be at least 6 characters');
            }
            $updateData['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }
        
        $this->repository->update($id, $updateData);
        
        return $this->find($id);
    }
    
    /**
     * Delete user
     */
    public function delete($id)
    {
        $user = $this->repository->findById($id);
        if (!$user) {
            throw new NotFoundException('User not found');
        }
        
        $this->repository->delete($id);
        return true;
    }
    
    /**
     * Format user for response
     */
    private function formatUser($user)
    {
        $formatted = [
            'id' => (string)$user['_id'],
            'username' => $user['username'] ?? '',
            'email' => $user['email'] ?? '',
            'role' => $user['role'] ?? 'admin',
            'status' => $user['status'] ?? 'active'
        ];
        
        if (isset($user['created_at'])) {
            $formatted['created_at'] = $user['created_at']->toDateTime()->format('Y-m-d H:i:s');
        }
        
        if (isset($user['created_by'])) {
            $formatted['created_by'] = $user['created_by'];
        }
        
        if (isset($user['updated_at'])) {
            $formatted['updated_at'] = $user['updated_at']->toDateTime()->format('Y-m-d H:i:s');
        }
        
        if (isset($user['updated_by'])) {
            $formatted['updated_by'] = $user['updated_by'];
        }
        
        if (isset($user['last_login']) && $user['last_login']) {
            $formatted['last_login'] = $user['last_login']->toDateTime()->format('Y-m-d H:i:s');
        } else {
            $formatted['last_login'] = 'Never';
        }
        
        return $formatted;
    }
}
?>
