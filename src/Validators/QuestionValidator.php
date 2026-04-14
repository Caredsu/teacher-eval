<?php
/**
 * Question Validator
 */

namespace App\Validators;

class QuestionValidator extends Validator
{
    /**
     * Validate question creation
     */
    public function validateCreate($data)
    {
        $this->errors = [];
        
        // Question text
        if (!isset($data['question_text']) || !$this->required('question_text', $data['question_text'])) {
            $this->addError('question_text', 'Question text is required');
            return;
        }
        $this->max('question_text', $data['question_text'], 1000);
        
        // Display Order (optional)
        if (isset($data['display_order'])) {
            $this->numeric('display_order', $data['display_order']);
        }
        
        // Required (optional boolean)
        if (isset($data['required']) && !is_bool($data['required'])) {
            $this->addError('required', 'Required must be a boolean');
        }
        
        // Status (optional)
        if (isset($data['status'])) {
            $this->in('status', $data['status'], ['active', 'inactive']);
        }
    }
    
    /**
     * Validate question update
     */
    public function validateUpdate($data)
    {
        $this->errors = [];
        
        // Question text
        if (isset($data['question_text'])) {
            $this->max('question_text', $data['question_text'], 1000);
        }
        
        // Display Order
        if (isset($data['display_order'])) {
            $this->numeric('display_order', $data['display_order']);
        }
        
        // Required
        if (isset($data['required']) && !is_bool($data['required'])) {
            $this->addError('required', 'Required must be a boolean');
        }
        
        // Status
        if (isset($data['status'])) {
            $this->in('status', $data['status'], ['active', 'inactive']);
        }
    }
}
?>
