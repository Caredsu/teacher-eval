<?php
/**
 * Evaluation Repository - Database queries for evaluations
 */

namespace App\Repositories;

use MongoDB\BSON\ObjectId;

class EvaluationRepository
{
    private $collection;
    
    public function __construct($db)
    {
        $this->collection = $db->selectCollection('evaluations');
    }
    
    /**
     * Get all evaluations
     */
    public function getAll($limit = null, $skip = 0)
    {
        $options = ['sort' => ['submitted_at' => -1]];
        if ($limit) {
            $options['limit'] = $limit;
            $options['skip'] = $skip;
        }
        
        return $this->collection->find([], $options)->toArray();
    }
    
    /**
     * Get evaluations for teacher
     */
    public function getByTeacher($teacherId, $limit = null, $skip = 0)
    {
        try {
            $filter = ['teacher_id' => new ObjectId($teacherId)];
            $options = ['sort' => ['submitted_at' => -1]];
            
            if ($limit) {
                $options['limit'] = $limit;
                $options['skip'] = $skip;
            }
            
            return $this->collection->find($filter, $options)->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }
    
    /**
     * Count total evaluations
     */
    public function count()
    {
        return $this->collection->countDocuments();
    }
    
    /**
     * Count evaluations for teacher
     */
    public function countByTeacher($teacherId)
    {
        try {
            return $this->collection->countDocuments(['teacher_id' => new ObjectId($teacherId)]);
        } catch (\Exception $e) {
            return 0;
        }
    }
    
    /**
     * Count evaluations for period
     */
    public function countByPeriod($period)
    {
        return $this->collection->countDocuments(['period' => $period]);
    }
    
    /**
     * Find evaluation by ID
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
     * Check if teacher already evaluated
     */
    public function teacherEvaluated($teacherId, $period = null)
    {
        $filter = ['teacher_id' => new ObjectId($teacherId)];
        if ($period) {
            $filter['period'] = $period;
        }
        
        return $this->collection->findOne($filter) !== null;
    }
    
    /**
     * Create evaluation
     */
    public function create($evaluationData)
    {
        $result = $this->collection->insertOne($evaluationData);
        return $result->getInsertedId();
    }
    
    /**
     * Update evaluation
     */
    public function update($id, $updateData)
    {
        return $this->collection->updateOne(
            ['_id' => new ObjectId($id)],
            ['$set' => $updateData]
        );
    }
    
    /**
     * Delete evaluation
     */
    public function delete($id)
    {
        return $this->collection->deleteOne(['_id' => new ObjectId($id)]);
    }
}
?>
