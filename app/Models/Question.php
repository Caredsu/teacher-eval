<?php
/**
 * Question Model
 * Handles question data operations
 */

namespace App\Models;

class Question
{
    private $collection;

    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    /**
     * Get all questions
     */
    public function getAll($limit = null, $skip = 0)
    {
        $options = ['sort' => ['created_at' => -1]];
        if ($limit) {
            $options['limit'] = $limit;
            $options['skip'] = $skip;
        }
        return $this->collection->find([], $options);
    }

    /**
     * Get question by ID
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
     * Get questions grouped by category
     */
    public function getByCategory()
    {
        $pipeline = [
            ['$group' => [
                '_id' => '$category',
                'questions' => ['$push' => '$$ROOT'],
                'count' => ['$sum' => 1]
            ]],
            ['$sort' => ['_id' => 1]]
        ];
        return $this->collection->aggregate($pipeline);
    }

    /**
     * Create new question
     */
    public function create($data)
    {
        $question = [
            'question_text' => $data['question_text'] ?? '',
            'category' => $data['category'] ?? 'General',
            'question_type' => $data['question_type'] ?? 'rating',
            'created_at' => new \MongoDB\BSON\UTCDateTime(),
            'updated_at' => new \MongoDB\BSON\UTCDateTime()
        ];

        $result = $this->collection->insertOne($question);
        return $result->getInsertedId();
    }

    /**
     * Update question
     */
    public function update($id, $data)
    {
        $update = [
            '$set' => [
                'question_text' => $data['question_text'] ?? '',
                'category' => $data['category'] ?? 'General',
                'question_type' => $data['question_type'] ?? 'rating',
                'updated_at' => new \MongoDB\BSON\UTCDateTime()
            ]
        ];

        return $this->collection->updateOne(
            ['_id' => new \MongoDB\BSON\ObjectId($id)],
            $update
        );
    }

    /**
     * Delete question
     */
    public function delete($id)
    {
        return $this->collection->deleteOne(['_id' => new \MongoDB\BSON\ObjectId($id)]);
    }

    /**
     * Get distinct categories
     */
    public function getCategories()
    {
        return $this->collection->distinct('category');
    }
}
