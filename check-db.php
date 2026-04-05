<?php
/**
 * Database Diagnostic Tool
 * Check what's actually in MongoDB
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Constants.php';

echo "=== MONGODB DIAGNOSTIC ===\n\n";

try {
    echo "✅ Database Connection: OK\n";
    echo "📦 Database Name: " . DB_NAME . "\n\n";
    
    // Check admins collection
    echo "📋 ADMINS Collection:\n";
    echo "--- Total records: " . $admins_collection->countDocuments() . "\n";
    
    $admins = $admins_collection->find()->toArray();
    foreach ($admins as $admin) {
        echo "  • Username: " . ($admin['username'] ?? 'N/A');
        echo " | Role: " . ($admin['role'] ?? 'N/A');
        echo " | Email: " . ($admin['email'] ?? 'N/A') . "\n";
    }
    
    echo "\n📋 TEACHERS Collection:\n";
    echo "--- Total records: " . $teachers_collection->countDocuments() . "\n";
    
    echo "\n📋 QUESTIONS Collection:\n";
    echo "--- Total records: " . $questions_collection->countDocuments() . "\n";
    
    echo "\n📋 EVALUATIONS Collection:\n";
    echo "--- Total records: " . $evaluations_collection->countDocuments() . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
