<?php
/**
 * HTTP Request Handler
 * Abstracts request data, validation, and input handling
 */

namespace App\Core;

class Request
{
    protected $data = [];
    protected $headers = [];
    protected $files = [];
    
    public function __construct()
    {
        $this->data = $this->parseRequestData();
        $this->headers = getallheaders();
        $this->files = $_FILES;
    }
    
    /**
     * Parse request data from JSON or form data
     */
    private function parseRequestData()
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            // JSON request body
            $body = file_get_contents('php://input');
            return json_decode($body, true) ?? [];
        } else {
            // Form data or query string
            return array_merge($_GET, $_POST);
        }
    }
    
    /**
     * Get request data
     */
    public function all()
    {
        return $this->data;
    }
    
    /**
     * Get specific field
     */
    public function get($key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }
    
    /**
     * Check if field exists
     */
    public function has($key)
    {
        return isset($this->data[$key]) && !empty($this->data[$key]);
    }
    
    /**
     * Get only specified fields
     */
    public function only($keys)
    {
        $result = [];
        foreach ((array)$keys as $key) {
            if (isset($this->data[$key])) {
                $result[$key] = $this->data[$key];
            }
        }
        return $result;
    }
    
    /**
     * Get request method
     */
    public function method()
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }
    
    /**
     * Check request method
     */
    public function isMethod($method)
    {
        return $this->method() === strtoupper($method);
    }
    
    /**
     * Get request path
     */
    public function path()
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        return trim($path, '/');
    }
    
    /**
     * Get header value
     */
    public function header($key, $default = null)
    {
        return $this->headers[$key] ?? $default;
    }
    
    /**
     * Get authorization token
     */
    public function bearerToken()
    {
        $header = $this->header('Authorization');
        if ($header && preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }
    
    /**
     * Get uploaded file
     */
    public function file($key)
    {
        return $this->files[$key] ?? null;
    }
    
    /**
     * Validate request data
     */
    public function validate($rules)
    {
        $errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $this->get($field);
            $rules_array = explode('|', $fieldRules);
            
            foreach ($rules_array as $rule) {
                $error = $this->validateRule($field, $value, $rule);
                if ($error) {
                    $errors[$field][] = $error;
                }
            }
        }
        
        return empty($errors) ? [] : $errors;
    }
    
    /**
     * Validate single rule
     */
    private function validateRule($field, $value, $rule)
    {
        if (strpos($rule, ':') !== false) {
            [$rule, $param] = explode(':', $rule, 2);
        } else {
            $param = null;
        }
        
        switch ($rule) {
            case 'required':
                return empty($value) ? "$field is required" : null;
            case 'email':
                return !filter_var($value, FILTER_VALIDATE_EMAIL) ? "$field must be a valid email" : null;
            case 'min':
                return strlen($value) < $param ? "$field must be at least $param characters" : null;
            case 'max':
                return strlen($value) > $param ? "$field must not exceed $param characters" : null;
            case 'numeric':
                return !is_numeric($value) ? "$field must be numeric" : null;
            case 'in':
                $options = explode(',', $param);
                return !in_array($value, $options) ? "$field must be one of: " . implode(', ', $options) : null;
            default:
                return null;
        }
    }
}
?>
