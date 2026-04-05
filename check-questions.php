<?php
/**
 * Check Questions in MongoDB
 */

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';

echo "=== CHECKING QUESTIONS IN MONGODB ===\n\n";

try {
    $count = $questions_collection->countDocuments();
    echo "✅ Total Questions: $count\n\n";
    
    if ($count > 0) {
        echo "📋 QUESTIONS:\n";
        echo "================================================\n";
        
        $questions = $questions_collection->find()->toArray();
        
        foreach ($questions as $i => $q) {
            echo "\n" . ($i + 1) . ". " . ($q['question_text'] ?? $q['title'] ?? 'N/A') . "\n";
            echo "   ID: " . ($q['_id'] ?? 'N/A') . "\n";
            echo "   Category: " . ($q['category'] ?? 'N/A') . "\n";
            echo "   Type: " . ($q['type'] ?? 'N/A') . "\n";
            echo "   Status: " . ($q['status'] ?? 'N/A') . "\n";
            echo "   Full Data: " . json_encode($q, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    } else {
        echo "❌ NO QUESTIONS FOUND IN DATABASE\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
