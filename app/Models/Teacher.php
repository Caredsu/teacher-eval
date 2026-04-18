<?php
/**
 * Teacher Model
 * Handles teacher data operations
 */

namespace App\Models;

class Teacher
{
    private $collection;

    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    /**
     * Get all teachers
     */
    public function getAll($limit = null, $skip = 0)
    {
        $options = ['sort' => ['name' => 1]];
        if ($limit) {
            $options['limit'] = $limit;
            $options['skip'] = $skip;
        }
        return $this->collection->find([], $options);
    }

    /**
     * Get teacher by ID
     */
    public function getById($id)
    {
        return $this->collection->findOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
    }

    /**
     * Get total count
     */
    public function count()
    {
        return $this->collection->countDocuments();
    }

    /**
     * Create new teacher
     */
    public function create($data)
    {
        $teacher = [
            'name' => $data['name'] ?? '',
            'department' => $data['department'] ?? '',
            'email' => $data['email'] ?? '',
            'created_at' => new \MongoDB\BSON\UTCDateTime(),
            'updated_at' => new \MongoDB\BSON\UTCDateTime()
        ];

        $result = $this->collection->insertOne($teacher);
        return $result->getInsertedId();
    }

    /**
     * Update teacher
     */
    public function update($id, $data)
    {
        $update = [
            '$set' => [
                'name' => $data['name'] ?? '',
                'department' => $data['department'] ?? '',
                'email' => $data['email'] ?? '',
                'updated_at' => new \MongoDB\BSON\UTCDateTime()
            ]
        ];

        return $this->collection->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($id)],
            $update
        );
    }

    /**
     * Delete teacher
     */
    public function delete($id)
    {
        return $this->collection->deleteOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
    }

    /**
     * Get teacher with evaluation count
     */
    public function getWithStats($evaluationsCollection)
    {
        $teachers = [];
        foreach ($this->getAll() as $teacher) {
            $teacherId = (string)$teacher['_id'];
            $evalCount = $evaluationsCollection->countDocuments(['teacher_id' => $teacherId]);
            $teacher['evaluation_count'] = $evalCount;
            $teachers[] = $teacher;
        }
        return $teachers;
    }
}
