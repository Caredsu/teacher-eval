<?php
/**
 * Teacher Repository - Database queries for teachers
 */

namespace App\Repositories;

use MongoDB\BSON\ObjectId;

class TeacherRepository
{
    private $collection;
    
    public function __construct($db)
    {
        $this->collection = $db->selectCollection('teachers');
    }
    
    /**
     * Get all teachers with pagination
     */
    public function getAll($limit = null, $skip = 0)
    {
        $options = ['sort' => ['first_name' => 1, 'last_name' => 1]];
        if ($limit) {
            $options['limit'] = $limit;
            $options['skip'] = $skip;
        }
        
        $cursor = $this->collection->find([], $options);
        return $cursor->toArray();
    }
    
    /**
     * Count total teachers
     */
    public function count()
    {
        return $this->collection->countDocuments();
    }
    
    /**
     * Find teacher by ID
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
     * Find teacher by email
     */
    public function findByEmail($email)
    {
        return $this->collection->findOne(['email' => $email]);
    }
    
    /**
     * Find teachers by department
     */
    public function findByDepartment($department)
    {
        return $this->collection->find(['department' => $department])->toArray();
    }
    
    /**
     * Create teacher
     */
    public function create($teacherData)
    {
        $result = $this->collection->insertOne($teacherData);
        return $result->getInsertedId();
    }
    
    /**
     * Update teacher
     */
    public function update($id, $updateData)
    {
        return $this->collection->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => $updateData]
        );
    }
    
    /**
     * Delete teacher
     */
    public function delete($id)
    {
        return $this->collection->deleteOne(['_id' => new ObjectId($id)]);
    }
}
?>
