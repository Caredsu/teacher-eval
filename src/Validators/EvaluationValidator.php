<?php
/**
 * Evaluation Validator
 */

namespace App\Validators;

class EvaluationValidator extends Validator
{
    /**
     * Validate evaluation submission
     */
    public function validateSubmit($data)
    {
        $this->errors = [];
        
        // Teacher ID
        if (!isset($data['teacher_id']) || !$this->required('teacher_id', $data['teacher_id'])) {
            return;
        }
        
        // Answers
        if (!isset($data['answers'])) {
            $this->addError('answers', 'Answers are required');
            return;
        }
        
        if (!is_array($data['answers'])) {
            $this->addError('answers', 'Answers must be an array');
            return;
        }
        
        if (empty($data['answers'])) {
            $this->addError('answers', 'At least one answer is required');
            return;
        }
        
        // Validate each answer
        foreach ($data['answers'] as $index => $answer) {
            if (!isset($answer['question_id'])) {
                $this->addError("answers.{$index}.question_id", 'Question ID is required');
            }
            
            if (isset($answer['rating']) && !$this->numeric("answers.{$index}.rating", $answer['rating'])) {
                // Already added error
            }
        }
        
        // Feedback (optional)
        if (isset($data['feedback'])) {
            $this->max('feedback', $data['feedback'], 1000);
        }
    }
}
?>
