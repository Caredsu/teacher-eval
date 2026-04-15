<?php
/**
 * Teacher Service - Teacher Management Business Logic
 */

namespace App\Services;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

class TeacherService
{
    private $repository;
    
    public function __construct($repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * Get all teachers with pagination
     */
    public function all($page = 1, $limit = 10)
    {
        $skip = ($page - 1) * $limit;
        $teachers = $this->repository->getAll($limit, $skip);
        $total = $this->repository->count();
        
        // Format all teachers
        $formatted = array_map(function($teacher) {
            return $this->formatTeacher($teacher);
        }, $teachers);
        
        return [
            'data' => $formatted,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit)
        ];
    }
    
    /**
     * Get single teacher
     */
    public function find($id)
    {
        $teacher = $this->repository->findById($id);
        if (!$teacher) {
            throw new NotFoundException('Teacher not found');
        }
        return $this->formatTeacher($teacher);
    }
    
    /**
     * Create new teacher
     */
    public function create($data)
    {
        // Validate
        if (empty($data['first_name']) && empty($data['last_name'])) {
            throw new ValidationException('At least first or last name is required');
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new ValidationException('Valid email is required');
        }
        
        // Check email uniqueness
        if ($this->repository->findByEmail($data['email'])) {
            throw new ValidationException('Email already exists');
        }
        
        $teacherData = [
            'first_name' => $data['first_name'] ?? '',
            'middle_name' => $data['middle_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'email' => $data['email'],
            'department' => $data['department'] ?? '',
            'status' => $data['status'] ?? 'active',
            'picture' => $data['picture'] ?? null,
            'created_at' => new UTCDateTime(),
            'updated_at' => new UTCDateTime()
        ];
        
        $result = $this->repository->create($teacherData);
        
        return [
            'id' => (string)$result,
            'first_name' => $teacherData['first_name'],
            'last_name' => $teacherData['last_name'],
            'email' => $teacherData['email'],
            'department' => $teacherData['department']
        ];
    }
    
    /**
     * Update teacher
     */
    public function update($id, $data, $userId = null)
    {
        $teacher = $this->repository->findById($id);
        if (!$teacher) {
            throw new NotFoundException('Teacher not found');
        }
        
        $updateData = [
            'updated_at' => new UTCDateTime(),
            'updated_by' => $userId ?? 'system'
        ];
        
        if (isset($data['first_name'])) {
            $updateData['first_name'] = $data['first_name'];
        }
        
        if (isset($data['middle_name'])) {
            $updateData['middle_name'] = $data['middle_name'];
        }
        
        if (isset($data['last_name'])) {
            $updateData['last_name'] = $data['last_name'];
        }
        
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
        
        if (isset($data['department'])) {
            $updateData['department'] = $data['department'];
        }
        
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }
        
        if (isset($data['picture'])) {
            $updateData['picture'] = $data['picture'];
        }
        
        $this->repository->update($id, $updateData);
        
        return $this->find($id);
    }
    
    /**
     * Delete teacher
     */
    public function delete($id)
    {
        $teacher = $this->repository->findById($id);
        if (!$teacher) {
            throw new NotFoundException('Teacher not found');
        }
        
        $this->repository->delete($id);
        return true;
    }
    
    /**
     * Format teacher for response
     */
    private function formatTeacher($teacher)
    {
        // Handle both old (firstname/lastname) and new (first_name/last_name) field names
        $firstName = $teacher['first_name'] ?? $teacher['firstname'] ?? '';
        $middleName = $teacher['middle_name'] ?? $teacher['middlename'] ?? '';
        $lastName = $teacher['last_name'] ?? $teacher['lastname'] ?? '';
        
        // If name field exists but individual fields don't, parse it
        if (empty($firstName) && empty($lastName) && isset($teacher['name'])) {
            $nameParts = explode(' ', $teacher['name']);
            $firstName = $nameParts[0] ?? '';
            if (count($nameParts) > 2) {
                $middleName = $nameParts[1] ?? '';
                $lastName = $nameParts[2] ?? '';
            } elseif (count($nameParts) > 1) {
                $lastName = $nameParts[1] ?? '';
            }
        }
        
        $fullName = trim("$firstName $middleName $lastName");
        
        return [
            'id' => (string)$teacher['_id'],
            'first_name' => $firstName,
            'middle_name' => $middleName,
            'last_name' => $lastName,
            'full_name' => $fullName ?: 'Unknown',
            'email' => $teacher['email'] ?? '',
            'department' => $teacher['department'] ?? '',
            'status' => $teacher['status'] ?? 'active',
            'picture' => $teacher['picture'] ?? null,
            'created_at' => isset($teacher['created_at']) ? $teacher['created_at']->toDateTime()->format('Y-m-d H:i:s') : '',
            'updated_at' => isset($teacher['updated_at']) ? $teacher['updated_at']->toDateTime()->format('Y-m-d H:i:s') : '',
            'updated_by' => $teacher['updated_by'] ?? 'system'
        ];
    }
}
?>
