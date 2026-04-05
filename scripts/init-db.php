<?php
/**
 * Database Initialization Script - Admin System Setup
 * Run this file once: php scripts/init-db.php
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/helpers.php';

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

// Initialize session for logging
initializeSession();
$_SESSION['admin_username'] = 'setup_script';
$_SESSION['admin_role'] = 'super_admin';

$db = Database::getInstance();

echo "====== MongoDB Database Initialization ======\n\n";

try {
    // Drop existing collections
    echo "1. Clearing existing collections...\n";
    $collections = ['teachers', 'questions', 'evaluations', 'admins', 'activity_logs'];
    
    foreach ($collections as $collection) {
        try {
            $db->getDatabase()->selectCollection($collection)->drop();
            echo "   ✓ Dropped '$collection' collection\n";
        } catch (\Exception $e) {
            echo "   ℹ Collection '$collection' not found\n";
        }
    }
    
    echo "\n2. Creating admin users...\n";
    $adminsCollection = $db->getCollection('admins');
    
    $super_admin = [
        'username' => 'superadmin',
        'email' => 'superadmin@example.com',
        'password' => hashPassword('superadmin123'),
        'role' => 'super_admin',
        'status' => 'active',
        'created_at' => new UTCDateTime(),
        'created_by' => 'setup_script',
        'updated_at' => new UTCDateTime(),
        'updated_by' => 'setup_script',
        'last_login' => null
    ];
    
    $admin = [
        'username' => 'admin',
        'email' => 'admin@example.com',
        'password' => hashPassword('admin123'),
        'role' => 'admin',
        'status' => 'active',
        'created_at' => new UTCDateTime(),
        'created_by' => 'setup_script',
        'updated_at' => new UTCDateTime(),
        'updated_by' => 'setup_script',
        'last_login' => null
    ];
    
    $adminsCollection->insertMany([$super_admin, $admin]);
    echo "   ✓ Created super_admin (password: superadmin123)\n";
    echo "   ✓ Created admin (password: admin123)\n";
    
    echo "\n3. Creating sample teachers...\n";
    $teachersCollection = $db->getCollection('teachers');
    
    $teachers = [
        [
            'first_name' => 'John',
            'middle_name' => 'Michael',
            'last_name' => 'Smith',
            'department' => 'ECT',
            'email' => 'john.smith@college.edu',
            'status' => 'active',
            'created_at' => new UTCDateTime(),
            'created_by' => 'setup_script',
            'updated_at' => new UTCDateTime(),
            'updated_by' => 'setup_script'
        ],
        [
            'first_name' => 'Jane',
            'middle_name' => 'Mary',
            'last_name' => 'Johnson',
            'department' => 'EDUC',
            'email' => 'jane.johnson@college.edu',
            'status' => 'active',
            'created_at' => new UTCDateTime(),
            'created_by' => 'setup_script',
            'updated_at' => new UTCDateTime(),
            'updated_by' => 'setup_script'
        ],
        [
            'first_name' => 'Robert',
            'middle_name' => 'James',
            'last_name' => 'Davis',
            'department' => 'CCJE',
            'email' => 'robert.davis@college.edu',
            'status' => 'active',
            'created_at' => new UTCDateTime(),
            'created_by' => 'setup_script',
            'updated_at' => new UTCDateTime(),
            'updated_by' => 'setup_script'
        ],
        [
            'first_name' => 'Sarah',
            'middle_name' => 'Louise',
            'last_name' => 'Williams',
            'department' => 'BHT',
            'email' => 'sarah.williams@college.edu',
            'status' => 'active',
            'created_at' => new UTCDateTime(),
            'created_by' => 'setup_script',
            'updated_at' => new UTCDateTime(),
            'updated_by' => 'setup_script'
        ]
    ];
    
    $teachersCollection->insertMany($teachers);
    echo "   ✓ Created " . count($teachers) . " sample teachers\n";
    
    echo "\n4. Creating evaluation questions...\n";
    $questionsCollection = $db->getCollection('questions');
    
    $questions = [
        [
            'question_text' => 'The instructor was knowledgeable about the course material',
            'category' => 'Knowledge',
            'question_type' => 'rating',
            'question_order' => 1,
            'status' => 'active',
            'created_at' => new UTCDateTime(),
            'created_by' => 'setup_script',
            'updated_at' => new UTCDateTime(),
            'updated_by' => 'setup_script'
        ],
        [
            'question_text' => 'The instructor communicated effectively',
            'category' => 'Communication',
            'question_type' => 'rating',
            'question_order' => 2,
            'status' => 'active',
            'created_at' => new UTCDateTime(),
            'created_by' => 'setup_script',
            'updated_at' => new UTCDateTime(),
            'updated_by' => 'setup_script'
        ],
        [
            'question_text' => 'The instructor was well-prepared for class',
            'category' => 'Preparation',
            'question_type' => 'rating',
            'question_order' => 3,
            'status' => 'active',
            'created_at' => new UTCDateTime(),
            'created_by' => 'setup_script',
            'updated_at' => new UTCDateTime(),
            'updated_by' => 'setup_script'
        ],
        [
            'question_text' => 'The instructor engaged students in learning',
            'category' => 'Engagement',
            'question_type' => 'rating',
            'question_order' => 4,
            'status' => 'active',
            'created_at' => new UTCDateTime(),
            'created_by' => 'setup_script',
            'updated_at' => new UTCDateTime(),
            'updated_by' => 'setup_script'
        ],
        [
            'question_text' => 'The instructor provided constructive feedback',
            'category' => 'Feedback',
            'question_type' => 'rating',
            'question_order' => 5,
            'status' => 'active',
            'created_at' => new UTCDateTime(),
            'created_by' => 'setup_script',
            'updated_at' => new UTCDateTime(),
            'updated_by' => 'setup_script'
        ]
    ];
    
    $questionsCollection->insertMany($questions);
    echo "   ✓ Created " . count($questions) . " evaluation questions\n";
    
    echo "\n5. Creating indexes...\n";
    
    // Teachers indexes
    $teachersCollection->createIndex(['email' => 1], ['unique' => true, 'sparse' => true]);
    echo "   ✓ Created email index on teachers\n";
    
    $teachersCollection->createIndex(['department' => 1]);
    echo "   ✓ Created department index on teachers\n";
    
    $teachersCollection->createIndex(['status' => 1]);
    echo "   ✓ Created status index on teachers\n";
    
    // Questions indexes
    $questionsCollection->createIndex(['question_order' => 1]);
    echo "   ✓ Created order index on questions\n";
    
    $questionsCollection->createIndex(['status' => 1]);
    echo "   ✓ Created status index on questions\n";
    
    // Admins indexes
    $adminsCollection->createIndex(['username' => 1], ['unique' => true]);
    echo "   ✓ Created username index on admins\n";
    
    // Evaluations indexes
    $evaluationsCollection = $db->getCollection('evaluations');
    $evaluationsCollection->createIndex(['teacher_id' => 1]);
    echo "   ✓ Created teacher_id index on evaluations\n";
    
    $evaluationsCollection->createIndex(['created_at' => -1]);
    echo "   ✓ Created created_at index on evaluations\n";
    
    // Activity logs indexes
    $activityCollection = $db->getCollection('activity_logs');
    $activityCollection->createIndex(['admin_username' => 1]);
    echo "   ✓ Created admin_username index on activity_logs\n";
    
    $activityCollection->createIndex(['timestamp' => -1]);
    echo "   ✓ Created timestamp index on activity_logs\n";
    
    echo "\n====== ✓ Database Initialization Complete! ======\n\n";
    echo "Login Credentials:\n";
    echo "  Super Admin:\n";
    echo "    Username: superadmin\n";
    echo "    Password: superadmin123\n\n";
    echo "  Admin:\n";
    echo "    Username: admin\n";
    echo "    Password: admin123\n\n";
    echo "Access the admin panel at: http://localhost/admin/login.php\n";
    
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
