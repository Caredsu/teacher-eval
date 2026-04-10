<?php
/**
 * Teacher Validator
 */

namespace App\Validators;

class TeacherValidator extends Validator
{
    /**
     * Validate teacher creation
     */
    public function validateCreate($data)
    {
        $this->errors = [];
        
        // Name
        if (!isset($data['name']) || !$this->required('name', $data['name'])) {
            return;
        }
        $this->max('name', $data['name'], 100);
        
        // Email
        if (!isset($data['email']) || !$this->required('email', $data['email'])) {
            return;
        }
        $this->email('email', $data['email']);
        
        // Department (optional)
        if (isset($data['department'])) {
            $this->max('department', $data['department'], 100);
        }
        
        // Subject (optional)
        if (isset($data['subject'])) {
            $this->max('subject', $data['subject'], 100);
        }
        
        // Status
        if (isset($data['status'])) {
            $this->in('status', $data['status'], ['active', 'inactive', 'on_leave']);
        }
    }
    
    /**
     * Validate teacher update
     */
    public function validateUpdate($data)
    {
        $this->errors = [];
        
        // Name
        if (isset($data['name'])) {
            $this->max('name', $data['name'], 100);
        }
        
        // Email
        if (isset($data['email'])) {
            $this->email('email', $data['email']);
        }
        
        // Department
        if (isset($data['department'])) {
            $this->max('department', $data['department'], 100);
        }
        
        // Subject
        if (isset($data['subject'])) {
            $this->max('subject', $data['subject'], 100);
        }
        
        // Status
        if (isset($data['status'])) {
            $this->in('status', $data['status'], ['active', 'inactive', 'on_leave']);
        }
    }
}
?>
