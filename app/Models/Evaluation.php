<?php
/**
 * Evaluation Model
 * Handles evaluation data operations
 */

namespace App\Models;

class Evaluation
{
    private $collection;

    public function __construct($collection)
    {
        $this->collection = $collection;
    }

    /**
     * Get all evaluations
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
     * Get evaluations by teacher ID
     */
    public function getByTeacherId($teacherId, $limit = null)
    {
        $options = ['sort' => ['created_at' => -1]];
        if ($limit) {
            $options['limit'] = $limit;
        }
        return $this->collection->find(['teacher_id' => $teacherId], $options);
    }

    /**
     * Get total count
     */
    public function count()
    {
        return $this->collection->countDocuments();
    }

    /**
     * Create new evaluation
     */
    public function create($data)
    {
        $evaluation = [
            'teacher_id' => $data['teacher_id'] ?? '',
            'subject' => $data['subject'] ?? '',
            'answers' => $data['answers'] ?? [],
            'ip_address' => $data['ip_address'] ?? $_SERVER['REMOTE_ADDR'],
            'user_agent' => $data['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => new \MongoDB\BSON\UTCDateTime(),
        ];

        $result = $this->collection->insertOne($evaluation);
        return $result->getInsertedId();
    }

    /**
     * Get average rating for teacher
     */
    public function getAverageRatingByTeacher($teacherId)
    {
        $pipeline = [
            ['$match' => ['teacher_id' => $teacherId]],
            ['$unwind' => '$answers'],
            ['$group' => [
                '_id' => null,
                'avg_rating' => ['$avg' => '$answers.rating'],
                'count' => ['$sum' => 1]
            ]]
        ];

        $result = $this->collection->aggregate($pipeline);
        foreach ($result as $doc) {
            return $doc;
        }
        return null;
    }

    /**
     * Get teacher ratings summary
     */
    public function getTeacherRatingsSummary($teachersCollection)
    {
        $ratings = [];
        foreach ($teachersCollection->find() as $teacher) {
            $teacherId = (string)$teacher['_id'];
            $avgData = $this->getAverageRatingByTeacher($teacherId);
            $ratings[] = [
                'teacher_id' => $teacherId,
                'name' => $teacher['name'] ?? 'Unknown',
                'avg_rating' => $avgData['avg_rating'] ?? 0,
                'evaluation_count' => $avgData['count'] ?? 0
            ];
        }
        return $ratings;
    }

    /**
     * Get evaluation statistics
     */
    public function getStatistics()
    {
        return [
            'total' => $this->count(),
            'today' => $this->countByDate(date('Y-m-d')),
            'this_week' => $this->countThisWeek(),
            'this_month' => $this->countThisMonth()
        ];
    }

    /**
     * Count evaluations by date
     */
    private function countByDate($date)
    {
        $start = new \MongoDB\BSON\UTCDateTime(strtotime($date) * 1000);
        $end = new \MongoDB\BSON\UTCDateTime((strtotime($date) + 86400) * 1000);

        return $this->collection->countDocuments([
            'created_at' => ['$gte' => $start, '$lt' => $end]
        ]);
    }

    /**
     * Count evaluations this week
     */
    private function countThisWeek()
    {
        $startOfWeek = strtotime('monday this week');
        $start = new \MongoDB\BSON\UTCDateTime($startOfWeek * 1000);

        return $this->collection->countDocuments([
            'created_at' => ['$gte' => $start]
        ]);
    }

    /**
     * Count evaluations this month
     */
    private function countThisMonth()
    {
        $startOfMonth = strtotime('first day of this month');
        $start = new \MongoDB\BSON\UTCDateTime($startOfMonth * 1000);

        return $this->collection->countDocuments([
            'created_at' => ['$gte' => $start]
        ]);
    }
}
