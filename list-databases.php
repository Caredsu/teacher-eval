<?php
/**
 * Find the correct database with admin data
 */

require_once __DIR__ . '/vendor/autoload.php';

use MongoDB\Client as MongoClient;

try {
    $client = new MongoClient('mongodb://localhost:27017');
    $adminDB = $client->admin;
    
    // List all databases
    $result = $adminDB->command(['listDatabases' => 1]);
    $databases = $result->toArray()[0];
    
    echo "=== ALL MONGODB DATABASES ===\n\n";
    
    foreach ($databases['databases'] as $db) {
        $dbName = $db['name'];
        $dbSize = $db['sizeOnDisk'] ?? 0;
        
        echo "📦 Database: $dbName (Size: " . ($dbSize / 1024) . " KB)\n";
        
        // List collections in this database
        $database = $client->selectDatabase($dbName);
        $collections = $database->listCollections();
        
        foreach ($collections as $col) {
            $colName = $col->getName();
            $colCount = $database->selectCollection($colName)->countDocuments();
            echo "   └─ Collection: $colName ($colCount records)\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
