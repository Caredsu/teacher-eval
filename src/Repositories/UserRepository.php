<?php
/**
 * User Repository - Database queries for users
 */

namespace App\Repositories;

use MongoDB\BSON\ObjectId;

class UserRepository
{
    private $collection;
    
    public function __construct($db)
    {
        $this->collection = $db->selectCollection('admins');
    }
    
    /**
     * Get all users with pagination
     */
    public function getAll($limit = 10, $skip = 0)
    {
        $cursor = $this->collection->find([], [
            'sort' => ['created_at' => -1],
            'limit' => $limit,
            'skip' => $skip
        ]);
        
        return $cursor->toArray();
    }
    
    /**
     * Count total users
     */
    public function count()
    {
        return $this->collection->countDocuments();
    }
    
    /**
     * Find user by ID
     */
    public function findById($id)
    {
        try {
            return $this->collection->findOne(['_id' => new ObjectId($id)]);
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Find user by username
     */
    public function findByUsername($username)
    {
        return $this->collection->findOne(['username' => $username]);
    }
    
    /**
     * Find user by email
     */
    public function findByEmail($email)
    {
        return $this->collection->findOne(['email' => $email]);
    }
    
    /**
     * Create user
     */
    public function create($userData)
    {
        $result = $this->collection->insertOne($userData);
        return $result->getInsertedId();
    }
    
    /**
     * Update user
     */
    public function update($id, $updateData)
    {
        return $this->collection->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => $updateData]
        );
    }
    
    /**
     * Delete user
     */
    public function delete($id)
    {
        return $this->collection->deleteOne(['_id' => new ObjectId($id)]);
    }
}
?>
