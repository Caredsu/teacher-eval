<?php
/**
 * Validator Base Class
 * Provides common validation methods
 */

namespace App\Validators;

class Validator
{
    protected $errors = [];
    
    /**
     * Get all errors
     */
    public function errors()
    {
        return $this->errors;
    }
    
    /**
     * Check if validation passed
     */
    public function passes()
    {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     */
    public function fails()
    {
        return !$this->passes();
    }
    
    /**
     * Add error message
     */
    protected function addError($field, $message)
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
    
    /**
     * Validate required field
     */
    protected function required($field, $value)
    {
        if (empty($value)) {
            $this->addError($field, ucfirst($field) . ' is required');
            return false;
        }
        return true;
    }
    
    /**
     * Validate min length
     */
    protected function min($field, $value, $length)
    {
        if (strlen($value) < $length) {
            $this->addError($field, ucfirst($field) . ' must be at least ' . $length . ' characters');
            return false;
        }
        return true;
    }
    
    /**
     * Validate max length
     */
    protected function max($field, $value, $length)
    {
        if (strlen($value) > $length) {
            $this->addError($field, ucfirst($field) . ' must not exceed ' . $length . ' characters');
            return false;
        }
        return true;
    }
    
    /**
     * Validate email format
     */
    protected function email($field, $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, ucfirst($field) . ' must be a valid email address');
            return false;
        }
        return true;
    }
    
    /**
     * Validate field is numeric
     */
    protected function numeric($field, $value)
    {
        if (!is_numeric($value)) {
            $this->addError($field, ucfirst($field) . ' must be numeric');
            return false;
        }
        return true;
    }
    
    /**
     * Validate field is in array
     */
    protected function in($field, $value, $values)
    {
        if (!in_array($value, $values)) {
            $this->addError($field, ucfirst($field) . ' must be one of: ' . implode(', ', $values));
            return false;
        }
        return true;
    }
}
?>
