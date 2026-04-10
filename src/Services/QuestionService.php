<?php
/**
 * Question Service - Question Management Business Logic
 */

namespace App\Services;

use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

class QuestionService
{
    private $repository;
    
    public function __construct($repository)
    {
        $this->repository = $repository;
    }
    
    /**
     * Get all questions
     */
    public function all()
    {
        $questions = $this->repository->getAll();
        
        // Format all questions
        return array_map(function($question) {
            return $this->formatQuestion($question);
        }, $questions);
    }
    
    /**
     * Get single question
     */
    public function find($id)
    {
        $question = $this->repository->findById($id);
        if (!$question) {
            throw new NotFoundException('Question not found');
        }
        return $this->formatQuestion($question);
    }
    
    /**
     * Create new question
     */
    public function create($data)
    {
        if (empty($data['question_text'])) {
            throw new ValidationException('Question text is required');
        }
        
        if (empty($data['type']) || !in_array($data['type'], ['rating', 'text', 'multiple_choice'])) {
            throw new ValidationException('Invalid question type');
        }
        
        $questionData = [
            'question_text' => $data['question_text'],
            'type' => $data['type'],
            'category' => $data['category'] ?? '',
            'display_order' => $data['display_order'] ?? 0,
            'required' => $data['required'] ?? true,
            'status' => $data['status'] ?? 'active',
            'created_at' => new UTCDateTime(),
            'updated_at' => new UTCDateTime()
        ];
        
        $result = $this->repository->create($questionData);
        
        return [
            'id' => (string)$result,
            'question_text' => $questionData['question_text'],
            'type' => $questionData['type']
        ];
    }
    
    /**
     * Update question
     */
    public function update($id, $data)
    {
        $question = $this->repository->findById($id);
        if (!$question) {
            throw new NotFoundException('Question not found');
        }
        
        $updateData = ['updated_at' => new UTCDateTime()];
        
        if (isset($data['question_text'])) {
            $updateData['question_text'] = $data['question_text'];
        }
        
        if (isset($data['type'])) {
            if (!in_array($data['type'], ['rating', 'text', 'multiple_choice'])) {
                throw new ValidationException('Invalid question type');
            }
            $updateData['type'] = $data['type'];
        }
        
        if (isset($data['category'])) {
            $updateData['category'] = $data['category'];
        }
        
        if (isset($data['display_order'])) {
            $updateData['display_order'] = $data['display_order'];
        }
        
        if (isset($data['required'])) {
            $updateData['required'] = $data['required'];
        }
        
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }
        
        $this->repository->update($id, $updateData);
        
        return $this->find($id);
    }
    
    /**
     * Delete question
     */
    public function delete($id)
    {
        $question = $this->repository->findById($id);
        if (!$question) {
            throw new NotFoundException('Question not found');
        }
        
        $this->repository->delete($id);
        return true;
    }
    
    /**
     * Format question for response
     */
    private function formatQuestion($question)
    {
        return [
            'id' => (string)$question['_id'],
            'question_text' => $question['question_text'] ?? '',
            'type' => $question['type'] ?? 'rating',
            'category' => $question['category'] ?? '',
            'display_order' => $question['display_order'] ?? 0,
            'required' => $question['required'] ?? true,
            'status' => $question['status'] ?? 'active'
        ];
    }
}
?>
