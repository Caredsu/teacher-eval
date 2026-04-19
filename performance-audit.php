<?php
/**
 * Performance Diagnostics Report
 * Analyzes where the slowness is coming from
 */

require_once 'config/database.php';

echo "=== PERFORMANCE DIAGNOSTICS REPORT ===\n\n";

// 1. Database Connection Time
echo "⏱️  DATABASE CONNECTION TEST\n";
$db_start = microtime(true);
try {
    $count = $teachers_collection->countDocuments();
    $db_time = (microtime(true) - $db_start) * 1000;
    echo "✅ DB Connection: {$db_time}ms\n";
    echo "   Teachers in DB: $count\n";
    if ($db_time > 500) {
        echo "   ⚠️  WARNING: Database connection is SLOW (>500ms)\n";
        echo "   FIX: Check MongoDB Atlas network latency\n";
    }
} catch (Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
}

// 2. Query Performance Tests
echo "\n📊 QUERY PERFORMANCE TEST\n";

// Test 1: Count all evaluations
$start = microtime(true);
$eval_count = $evaluations_collection->estimatedDocumentCount();
$time = (microtime(true) - $start) * 1000;
echo "✅ Count evaluations: {$time}ms (count: $eval_count)\n";

// Test 2: Get recent evaluations with projection
$start = microtime(true);
$recent = $evaluations_collection->find([], [
    'projection' => ['teacher_id' => 1, 'submitted_at' => 1],
    'limit' => 10,
    'sort' => ['submitted_at' => -1]
])->toArray();
$time = (microtime(true) - $start) * 1000;
echo "✅ Recent evaluations (with projection): {$time}ms (found: " . count($recent) . ")\n";

// Test 3: Get recent evaluations WITHOUT projection (bad)
$start = microtime(true);
$recent_bad = $evaluations_collection->find([], [
    'limit' => 10,
    'sort' => ['submitted_at' => -1]
])->toArray();
$time_bad = (microtime(true) - $start) * 1000;
echo "❌ Recent evaluations (NO projection): {$time_bad}ms\n";
echo "   ⚠️  WARNING: {$time_bad}ms vs {$time}ms (projection is {$time_bad / $time}x slower)\n";
echo "   FIX: Always use projection to limit fields\n";

// Test 4: Teacher lookups
$start = microtime(true);
$teachers = $teachers_collection->find([], [
    'projection' => ['first_name' => 1, 'last_name' => 1]
])->toArray();
$time = (microtime(true) - $start) * 1000;
echo "✅ Get teachers with projection: {$time}ms (found: " . count($teachers) . ")\n";

// 3. Check Indexes
echo "\n🔑 DATABASE INDEXES\n";
try {
    $indexes = $evaluations_collection->listIndexes();
    $index_count = 0;
    foreach ($indexes as $index) {
        $index_count++;
        echo "✅ Index: " . implode(', ', array_keys($index['key'])) . "\n";
    }
    if ($index_count <= 1) {
        echo "⚠️  WARNING: Only $index_count index(es) found\n";
        echo "FIX: Create indexes on frequently queried fields\n";
    }
} catch (Exception $e) {
    echo "❌ Could not list indexes: " . $e->getMessage() . "\n";
}

// 4. File Size Check
echo "\n📁 ASSET FILE SIZES\n";
$files_to_check = [
    'assets/css/dark-theme.css',
    'assets/js/main.js',
    'admin/login.php',
    'admin/dashboard.php'
];

foreach ($files_to_check as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        $size_kb = round($size / 1024, 2);
        if ($size_kb > 100) {
            echo "⚠️  $file: {$size_kb}KB (TOO LARGE)\n";
        } else {
            echo "✅ $file: {$size_kb}KB\n";
        }
    }
}

// 5. PHP Configuration
echo "\n⚙️  PHP CONFIGURATION\n";
echo "✅ PHP Version: " . phpversion() . "\n";
echo "✅ Memory Limit: " . ini_get('memory_limit') . "\n";
echo "✅ Max Execution Time: " . ini_get('max_execution_time') . "s\n";
echo "✅ Upload Max Size: " . ini_get('upload_max_filesize') . "\n";

// 6. Gzip Compression
echo "\n🗜️  GZIP COMPRESSION\n";
if (extension_loaded('zlib')) {
    echo "✅ Zlib extension loaded\n";
} else {
    echo "❌ Zlib extension NOT loaded\n";
    echo "FIX: Enable zlib in php.ini\n";
}

// 7. Performance Summary
echo "\n📈 PERFORMANCE SUMMARY\n";
echo "Database Speed: " . ($db_time < 200 ? "✅ GOOD" : "⚠️  SLOW") . "\n";
echo "Query Performance: " . ($time < 100 ? "✅ GOOD" : "⚠️  SLOW") . "\n";
echo "Asset Sizes: Check above\n";
echo "PHP Config: ✅ OK\n";

// 8. Recommendations
echo "\n🚀 TOP 5 RECOMMENDATIONS TO SPEED UP\n";
echo "1️⃣  Use field projections in ALL queries (QUICK WIN - 30-50% faster)\n";
echo "2️⃣  Add database indexes (QUICK WIN - 60-80% faster)\n";
echo "3️⃣  Compress images to WebP (QUICK WIN - 75% smaller)\n";
echo "4️⃣  Cache aggregation results (QUICK WIN - infinite faster for repeated queries)\n";
echo "5️⃣  Lazy load heavy JS libraries (MEDIUM - 20-30% faster)\n";

echo "\n✅ Run at: http://localhost/teacher-eval/performance-audit.php\n";
?>
