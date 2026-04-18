<?php
/**
 * MongoDB Database Configuration & Initialization
 * Singleton pattern - only ONE connection created globally
 */

// Load .env variables using vlucas/phpdotenv (MUST be first, only ONCE)
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
    if (class_exists('Dotenv\\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();
    }
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Constants.php';
/**
 * MongoDB Database Configuration & Initialization
 * Singleton pattern - only ONE connection created globally
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Constants.php';

use MongoDB\Client as MongoClient;

// DEBUG: Show actual DB_HOST being used
if (isset($_GET['debug_db']) && $_GET['debug_db'] === '1') {
    header('Content-Type: text/plain');
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : getenv('DB_HOST')) . "\n";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : getenv('DB_NAME')) . "\n";
    exit;
}
// SINGLETON: Static instance stored here
static $__mongo_instance = null;

if ($__mongo_instance === null) {
    try {
        // Always use DB_HOST from .env (Atlas or local)
        $uri = DB_HOST;
        
        $options = [
            'connectTimeoutMS' => 20000,         // Increased for Render cold starts
            'serverSelectionTimeoutMS' => 20000, // More time to find server
            'socketTimeoutMS' => 60000,          // Increased from 30000
            'maxPoolSize' => 10,                 // Reuse connections
            'minPoolSize' => 2,                  
            'waitQueueTimeoutMS' => 10000,       // Increased from 5000
            'retryWrites' => true,              
        ];
        
        $__mongo_instance = new MongoClient($uri, $options);
        $db = $__mongo_instance->selectDatabase(DB_NAME);

        // Ensure collections exist
        $collections_to_create = [
            COLLECTION_ADMINS,
            COLLECTION_TEACHERS,
            COLLECTION_QUESTIONS,
            COLLECTION_EVALUATIONS,
            COLLECTION_ACTIVITY_LOG,
            'system_settings'
        ];

        foreach ($collections_to_create as $col) {
            try {
                $db->createCollection($col);
            } catch (\Exception $e) {
                // Collection already exists
            }
        }
        
        // Set global db and collections
        global $db, $collections, $teachers_collection, $questions_collection, 
               $evaluations_collection, $admins_collection, $activity_log_collection, $settings_collection;
        
        $db = $db;
        $collections = [
            'admins' => $db->selectCollection(COLLECTION_ADMINS),
            'teachers' => $db->selectCollection(COLLECTION_TEACHERS),
            'questions' => $db->selectCollection(COLLECTION_QUESTIONS),
            'evaluations' => $db->selectCollection(COLLECTION_EVALUATIONS),
            'activity_log' => $db->selectCollection(COLLECTION_ACTIVITY_LOG),
            'system_settings' => $db->selectCollection('system_settings'),
        ];
        
        $teachers_collection = $collections['teachers'];
        $questions_collection = $collections['questions'];
        $evaluations_collection = $collections['evaluations'];
        $admins_collection = $collections['admins'];
        $activity_log_collection = $collections['activity_log'];
        $settings_collection = $collections['system_settings'];
        
        // Create indexes for performance
        try {
            // Evaluations indexes
            $evaluations_collection->createIndex(['teacher_id' => 1]);
            $evaluations_collection->createIndex(['academic_year' => 1]);
            $evaluations_collection->createIndex(['semester' => 1]);
            $evaluations_collection->createIndex(['period' => 1]);
            $evaluations_collection->createIndex(['submitted_at' => -1]);
            $evaluations_collection->createIndex([
                'teacher_id' => 1,
                'academic_year' => 1,
                'semester' => 1
            ]);
            
            // Duplicate prevention indexes
            $evaluations_collection->createIndex(['session_identifier' => 1]);
            $evaluations_collection->createIndex([
                'teacher_id' => 1,
                'ip_address' => 1,
                'user_agent' => 1
            ]);
            
            // Teachers indexes
            $teachers_collection->createIndex(['name' => 1]);
            $teachers_collection->createIndex(['email' => 1]);
            
            // Admins indexes
            $admins_collection->createIndex(['username' => 1], ['unique' => true]);
            $admins_collection->createIndex(['email' => 1], ['unique' => true]);
        } catch (\Exception $e) {
            // Indexes might already exist, that's ok
        }
        
    } catch (\Exception $e) {
        // Output JSON error instead of HTML
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database connection error',
            'error' => $e->getMessage(),
            'details' => [
                'connection_uri' => DB_HOST,
                'db_name' => DB_NAME
            ]
        ]);
        exit;
    }
}

// Backward compatibility function
function init_database()
{
    global $db, $collections;
    return [
        'db' => $db,
        'collections' => $collections
    ];
}