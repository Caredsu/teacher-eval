<?php
/**
 * User Validator
 */

namespace App\Validators;

class UserValidator extends Validator
{
    /**
     * Validate user creation
     */
    public function validateCreate($data)
    {
        $this->errors = [];
        
        // Username
        if (!isset($data['username']) || !$this->required('username', $data['username'])) {
            return;
        }
        $this->min('username', $data['username'], 3);
        $this->max('username', $data['username'], 50);
        
        // Email
        if (!isset($data['email']) || !$this->required('email', $data['email'])) {
            return;
        }
        $this->email('email', $data['email']);
        
        // Password
        if (!isset($data['password']) || !$this->required('password', $data['password'])) {
            return;
        }
        $this->min('password', $data['password'], 6);
        $this->max('password', $data['password'], 100);
        
        // Role
        if (isset($data['role'])) {
            $this->in('role', $data['role'], ['admin', 'staff', 'viewer']);
        }
        
        // Status
        if (isset($data['status'])) {
            $this->in('status', $data['status'], ['active', 'inactive', 'suspended']);
        }
    }
    
    /**
     * Validate user update
     */
    public function validateUpdate($data)
    {
        $this->errors = [];
        
        // Email (optional but validate if provided)
        if (isset($data['email'])) {
            $this->email('email', $data['email']);
        }
        
        // Password (optional but validate if provided)
        if (isset($data['password']) && !empty($data['password'])) {
            $this->min('password', $data['password'], 6);
            $this->max('password', $data['password'], 100);
        }
        
        // Role
        if (isset($data['role'])) {
            $this->in('role', $data['role'], ['admin', 'staff', 'viewer']);
        }
        
        // Status
        if (isset($data['status'])) {
            $this->in('status', $data['status'], ['active', 'inactive', 'suspended']);
        }
    }
}
?>
