<?php
/**
 * MongoDB Database Configuration & Initialization
 * Sets up database connection and initializes models
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Constants.php';

use MongoDB\Client as MongoClient;

/**
 * Initialize database connection
 */
function init_database()
{
    try {
        // MongoDB Atlas connection - use environment variable for security
        $uri = getenv('MONGODB_URI') ?: 'mongodb+srv://teacher_eval_user:alhamerpogi15@cluster0.xveslur.mongodb.net/teacher_evaluation?retryWrites=true&w=majority';
        $client = new MongoClient($uri);
        $db = $client->selectDatabase(DB_NAME);

        // Ensure collections exist
        $collections = [
            COLLECTION_ADMINS,
            COLLECTION_TEACHERS,
            COLLECTION_QUESTIONS,
            COLLECTION_EVALUATIONS,
            COLLECTION_ACTIVITY_LOG,
            'system_settings'
        ];

        foreach ($collections as $collection) {
            try {
                $db->createCollection($collection);
            } catch (\Exception $e) {
                // Collection already exists, ignore
            }
        }

        return [
            'db' => $db,
            'collections' => [
                'admins' => $db->selectCollection(COLLECTION_ADMINS),
                'teachers' => $db->selectCollection(COLLECTION_TEACHERS),
                'questions' => $db->selectCollection(COLLECTION_QUESTIONS),
                'evaluations' => $db->selectCollection(COLLECTION_EVALUATIONS),
                'activity_log' => $db->selectCollection(COLLECTION_ACTIVITY_LOG),
                'system_settings' => $db->selectCollection('system_settings'),
            ]
        ];
    } catch (\Exception $e) {
        die("MongoDB Connection Error: " . $e->getMessage());
    }
}

// Initialize database
$db_config = init_database();
$db = $db_config['db'];
$collections = $db_config['collections'];

// Legacy collection references (for backward compatibility)
$teachers_collection = $collections['teachers'];
$questions_collection = $collections['questions'];
$evaluations_collection = $collections['evaluations'];
$admins_collection = $collections['admins'];
$activity_log_collection = $collections['activity_log'];
$settings_collection = $collections['system_settings'];