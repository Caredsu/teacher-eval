<?php
/**
 * Question Repository - Database queries for questions
 */

namespace App\Repositories;

use MongoDB\BSON\ObjectId;

class QuestionRepository
{
    private $collection;
    
    public function __construct($db)
    {
        $this->collection = $db->selectCollection('questions');
    }
    
    /**
     * Get all questions sorted by order
     */
    public function getAll()
    {
        $cursor = $this->collection->find(
            ['status' => 'active'],
            ['sort' => ['display_order' => 1]]
        );
        
        return $cursor->toArray();
    }
    
    /**
     * Get all questions including inactive
     */
    public function getAllIncludeInactive()
    {
        return $this->collection->find([], ['sort' => ['display_order' => 1]])->toArray();
    }
    
    /**
     * Count total questions
     */
    public function count()
    {
        return $this->collection->countDocuments(['status' => 'active']);
    }
    
    /**
     * Find question by ID
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
     * Find by category
     */
    public function findByCategory($category)
    {
        return $this->collection->find(['category' => $category, 'active' => true])->toArray();
    }
    
    /**
     * Create question
     */
    public function create($questionData)
    {
        $result = $this->collection->insertOne($questionData);
        return $result->getInsertedId();
    }
    
    /**
     * Update question
     */
    public function update($id, $updateData)
    {
        return $this->collection->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => $updateData]
        );
    }
    
    /**
     * Delete question
     */
    public function delete($id)
    {
        return $this->collection->deleteOne(['_id' => new ObjectId($id)]);
    }
}
?>
