<?php
/**
 * Auth Service - Business Logic for Authentication
 * Handles login, registration, token verification
 */

namespace App\Services;

use App\Core\Response;
use App\Exceptions\AuthException;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;

class AuthService
{
    private $usersCollection;
    
    public function __construct($collection)
    {
        $this->usersCollection = $collection;
    }
    
    /**
     * Login admin user
     */
    public function login($username, $password)
    {
        // Find user
        $user = $this->usersCollection->findOne(['username' => $username]);
        
        if (!$user) {
            throw new AuthException('Invalid credentials');
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            throw new AuthException('Invalid credentials');
        }
        
        // Check if active
        if (($user['status'] ?? 'active') !== 'active') {
            throw new AuthException('Your account is deactivated');
        }
        
        // Update last login
        $this->usersCollection->updateOne(
            ['_id' => $user['_id']],
            ['$set' => ['last_login' => new UTCDateTime()]]
        );
        
        // Return user data (without password)
        return $this->formatUser($user);
    }
    
    /**
     * Create new user
     */
    public function createUser($data)
    {
        // Check if user exists
        if ($this->usersCollection->findOne(['username' => $data['username']])) {
            throw new AuthException('Username already exists');
        }
        
        if ($this->usersCollection->findOne(['email' => $data['email']])) {
            throw new AuthException('Email already exists');
        }
        
        // Create user
        $userData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
            'role' => $data['role'] ?? 'staff',
            'status' => 'active',
            'created_at' => new UTCDateTime(),
            'created_by' => $data['created_by'] ?? 'system',
            'updated_at' => new UTCDateTime(),
            'updated_by' => $data['created_by'] ?? 'system',
            'last_login' => null
        ];
        
        $result = $this->usersCollection->insertOne($userData);
        
        return [
            'id' => (string)$result->getInsertedId(),
            'username' => $userData['username'],
            'email' => $userData['email'],
            'role' => $userData['role']
        ];
    }
    
    /**
     * Format user for response (remove sensitive data)
     */
    private function formatUser($user)
    {
        $userData = [
            'id' => (string)$user['_id'],
            'username' => $user['username'],
            'email' => $user['email'] ?? '',
            'role' => $user['role'] ?? 'admin',
            'status' => $user['status'] ?? 'active'
        ];
        
        if (isset($user['last_login'])) {
            $userData['last_login'] = $user['last_login']->toDateTime()->format('Y-m-d H:i:s');
        }
        
        return $userData;
    }
}
?>
