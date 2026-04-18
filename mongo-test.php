<?php
// Direct MongoDB Atlas test
require_once __DIR__ . '/vendor/autoload.php';

use MongoDB\Client as MongoClient;

$uri = 'mongodb+srv://Fullbright:fCKPOHka7QbGIq1X@cluster0.d5w3hon.mongodb.net/?retryWrites=true&w=majority';

echo "=== MongoDB Connection Test ===\n\n";
echo "URI: " . str_replace($password = 'fCKPOHka7QbGIq1X', '***', $uri) . "\n\n";

try {
    echo "[1] Connecting to MongoDB Atlas...\n";
    $client = new MongoClient($uri, [
        'connectTimeoutMS' => 20000,
        'serverSelectionTimeoutMS' => 20000,
    ]);
    
    echo "[2] Pinging server...\n";
    $result = $client->admin->command(['ping' => 1]);
    
    echo "✅ SUCCESS!\n";
    echo "Response: " . json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "[3] Listing databases...\n";
    $databases = $client->listDatabases();
    foreach ($databases as $db) {
        echo "  - " . $db['name'] . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ FAILED!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}
?>
