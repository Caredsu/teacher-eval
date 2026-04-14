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
        
        // First Name or Last Name required
        if (empty($data['first_name']) && empty($data['last_name'])) {
            $this->addError('name', 'First name or last name is required');
            return;
        }
        
        // Email
        if (!isset($data['email']) || !$this->required('email', $data['email'])) {
            $this->addError('email', 'Email is required');
            return;
        }
        $this->email('email', $data['email']);
        
        // First name (optional)
        if (isset($data['first_name'])) {
            $this->max('first_name', $data['first_name'], 50);
        }
        
        // Last name (optional)
        if (isset($data['last_name'])) {
            $this->max('last_name', $data['last_name'], 50);
        }
        
        // Department (optional)
        if (isset($data['department'])) {
            $this->max('department', $data['department'], 100);
        }
        
        // Status (optional)
        if (isset($data['status'])) {
            $this->in('status', $data['status'], ['active', 'inactive']);
        }
        
        // Picture (optional - should be base64 encoded)
        if (isset($data['picture']) && !empty($data['picture'])) {
            // Validate that it looks like a base64 image data URL
            if (!preg_match('/^data:image\/\w+;base64,/', $data['picture'])) {
                $this->addError('picture', 'Picture must be a valid image');
            }
        }
    }
    
    /**
     * Validate teacher update
     */
    public function validateUpdate($data)
    {
        $this->errors = [];
        
        // First name (optional)
        if (isset($data['first_name'])) {
            $this->max('first_name', $data['first_name'], 50);
        }
        
        // Last name (optional)
        if (isset($data['last_name'])) {
            $this->max('last_name', $data['last_name'], 50);
        }
        
        // Email (optional)
        if (isset($data['email'])) {
            $this->email('email', $data['email']);
        }
        
        // Department (optional)
        if (isset($data['department'])) {
            $this->max('department', $data['department'], 100);
        }
        
        // Status (optional)
        if (isset($data['status'])) {
            $this->in('status', $data['status'], ['active', 'inactive']);
        }
        
        // Picture (optional - should be base64 encoded)
        if (isset($data['picture']) && !empty($data['picture'])) {
            // Validate that it looks like a base64 image data URL
            if (!preg_match('/^data:image\/\w+;base64,/', $data['picture'])) {
                $this->addError('picture', 'Picture must be a valid image');
            }
        }
    }
}
?>
