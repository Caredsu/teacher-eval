<?php
/**
 * Standard HTTP Response Handler
 * All API responses go through this for consistency
 */

namespace App\Core;

class Response
{
    /**
     * Send JSON response
     */
    public static function json($data, $message = '', $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        
        $response = [
            'success' => $status >= 200 && $status < 300,
            'status' => $status,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    /**
     * Success response (200 OK)
     */
    public static function success($data, $message = 'Success', $status = 200)
    {
        self::json($data, $message, $status);
    }
    
    /**
     * Created response (201 Created)
     */
    public static function created($data, $message = 'Resource created successfully')
    {
        self::json($data, $message, 201);
    }
    
    /**
     * Updated response (200 OK)
     */
    public static function updated($data, $message = 'Resource updated successfully')
    {
        self::json($data, $message, 200);
    }
    
    /**
     * Deleted response (200 OK)
     */
    public static function deleted($message = 'Resource deleted successfully')
    {
        self::json(null, $message, 200);
    }
    
    /**
     * Error response (400 Bad Request)
     */
    public static function error($message, $status = 400, $errors = null)
    {
        $data = $errors ? ['errors' => $errors] : null;
        self::json($data, $message, $status);
    }
    
    /**
     * Validation error (422 Unprocessable Entity)
     */
    public static function validation($errors, $message = 'Validation failed')
    {
        self::json(['errors' => $errors], $message, 422);
    }
    
    /**
     * Unauthorized response (401 Unauthorized)
     */
    public static function unauthorized($message = 'Unauthorized')
    {
        self::json(null, $message, 401);
    }
    
    /**
     * Forbidden response (403 Forbidden)
     */
    public static function forbidden($message = 'Forbidden')
    {
        self::json(null, $message, 403);
    }
    
    /**
     * Not found response (404 Not Found)
     */
    public static function notFound($message = 'Resource not found')
    {
        self::json(null, $message, 404);
    }
    
    /**
     * Server error response (500 Internal Server Error)
     */
    public static function serverError($message = 'Internal server error')
    {
        self::json(null, $message, 500);
    }
}
?>
