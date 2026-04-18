<?php
/**
 * Admin Model
 * Handles admin/user data operations
 */

namespace App\Models;

class Admin
{
    private $collection;

    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    /**
     * Get all admins
     */
    public function getAll()
    {
        return $this->collection->find([], ['sort' => ['created_at' => -1]]);
    }

    /**
     * Get admin by ID
     */
    public function getById($id)
    {
        return $this->collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
    }

    /**
     * Get admin by username
     */
    public function getByUsername($username)
    {
        return $this->collection->findOne(['username' => $username]);
    }

    /**
     * Create new admin
     */
    public function create($data)
    {
        $admin = [
            'username' => $data['username'] ?? '',
            'password' => $data['password'] ?? '',
            'email' => $data['email'] ?? '',
            'role' => $data['role'] ?? 'admin',
            'created_at' => new \MongoDB\BSON\UTCDateTime(),
            'updated_at' => new \MongoDB\BSON\UTCDateTime()
        ];

        $result = $this->collection->insertOne($admin);
        return $result->getInsertedId();
    }

    /**
     * Update admin password
     */
    public function updatePassword($id, $hashedPassword)
    {
        return $this->collection->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($id)],
            ['$set' => [
                'password' => $hashedPassword,
                'updated_at' => new \MongoDB\BSON\UTCDateTime()
            ]]
        );
    }

    /**
     * Check if username exists
     */
    public function usernameExists($username, $excludeId = null)
    {
        $filter = ['username' => $username];
        if ($excludeId) {
            $filter['_id'] = ['$ne' => new \MongoDB\BSON\ObjectId($excludeId)];
        }
        return $this->collection->findOne($filter) !== null;
    }
}
