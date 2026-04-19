# ⚡ QUICK REFERENCE GUIDE - Performance Optimizations

**Last Updated:** April 19, 2026  
**For:** Developers maintaining the system

---

## 🔍 OPTIMIZATION CHECKLIST

When writing code, remember these performance principles:

### ✅ Always Use Field Projections

```php
// ❌ BAD: Fetches all fields
$users = $collection->find([])->toArray();

// ✅ GOOD: Only fetch needed fields
$users = $collection->find([], [
    'projection' => [
        '_id' => 1,
        'username' => 1,
        'email' => 1
    ]
])->toArray();
```

### ✅ Avoid N+1 Queries

```php
// ❌ BAD: N+1 queries
foreach ($evaluations as $eval) {
    $teacher = $teachers->findOne(['_id' => $eval['teacher_id']]);
    // 100 evaluations = 100 queries!
}

// ✅ GOOD: Single aggregation
$result = $evaluations->aggregate([
    ['$lookup' => [
        'from' => 'teachers',
        'localField' => 'teacher_id',
        'foreignField' => '_id',
        'as' => 'teacher'
    ]]
]);
```

### ✅ Batch Fetch Related Data

```php
// ❌ BAD: Query for each item
foreach ($answers as $answer) {
    $question = $questions->findOne(['_id' => $answer['question_id']]);
}

// ✅ GOOD: Batch fetch
$question_ids = array_map(fn($a) => $a['question_id'], $answers);
$questions = $questions->find(['_id' => ['$in' => $question_ids]]);
```

### ✅ Use Response Caching

```php
require_once 'includes/cache.php';

// Check cache first
$key = 'expensive_data_' . date('Y-m-d');
$data = ResponseCache::get($key, 3600); // 1 hour TTL

if (!$data) {
    // Compute expensive operation
    $data = expensiveQuery();
    
    // Store in cache
    ResponseCache::set($key, $data, 3600);
}

return $data;
```

### ✅ Add Indexes for Filtered Queries

```php
// In database.php, add indexes for common queries:
$collection->createIndex(['status' => 1]);
$collection->createIndex(['created_at' => -1]);
$collection->createIndex(['teacher_id' => 1, 'submitted_at' => -1]);
```

### ✅ Filter Early in Aggregation Pipelines

```php
// ❌ BAD: Filter after complex operations
$pipeline = [
    ['$unwind' => '$items'],
    ['$group' => [...]],
    ['$match' => ['status' => 'active']]  // Filter at end
];

// ✅ GOOD: Filter at beginning
$pipeline = [
    ['$match' => ['status' => 'active']],  // Filter first
    ['$unwind' => '$items'],
    ['$group' => [...]]
];
```

---

## 📊 PERFORMANCE BENCHMARKS

### Page Load Targets

```
Target Performance:
├─ Login Page:          < 0.5s
├─ Dashboard:           < 1.0s
├─ Analytics:           < 1.5s
├─ API Endpoints:       < 300ms
└─ Database Queries:    < 200ms
```

### Response Size Targets

```
Target Response Sizes:
├─ Teachers List:       < 20KB
├─ Users List:          < 15KB
├─ Analytics Data:      < 50KB
└─ Evaluation Details:  < 10KB
```

---

## 🛠️ COMMON OPTIMIZATION PATTERNS

### Pattern 1: GET Endpoint with Projection

```php
<?php
// api/my-endpoint.php

require_once '../includes/cache.php';

// Cache key based on query parameters
$cache_key = 'endpoint_' . md5(json_encode($_GET));
$cached = ResponseCache::get($cache_key, 300); // 5 min TTL

if ($cached) {
    sendSuccess($cached, 'Retrieved from cache');
    exit;
}

// Fetch with projections
$data = $collection->find($filter, [
    'projection' => [
        '_id' => 1,
        'field1' => 1,
        'field2' => 1
    ],
    'sort' => [...],
    'limit' => 50
])->toArray();

// Cache result
ResponseCache::set($cache_key, $data, 300);

sendSuccess($data);
?>
```

### Pattern 2: Batch Operation

```php
<?php
// Instead of loop of queries, do batch operation
function getBatchData($ids) {
    $data = $collection->find(
        ['_id' => ['$in' => array_map('objectIdFn', $ids)]],
        ['projection' => ['_id' => 1, 'name' => 1]]
    )->toArray();
    
    // Create lookup map
    $map = [];
    foreach ($data as $item) {
        $map[(string)$item['_id']] = $item;
    }
    
    return $map;
}

// Usage
$ids = $evaluations->distinct('teacher_id');
$teachers = getBatchData($ids);

foreach ($evaluations as $eval) {
    $teacher = $teachers[(string)$eval['teacher_id']];
}
?>
```

### Pattern 3: Aggregation with Early Projection

```php
<?php
$pipeline = [
    // 1. Filter early
    ['$match' => $filter],
    
    // 2. Project only needed fields
    ['$project' => [
        '_id' => 1,
        'field1' => 1,
        'field2' => 1
    ]],
    
    // 3. Transform data
    ['$group' => [...] or '$unwind' => ...],
    
    // 4. Sort and limit
    ['$sort' => [...] ],
    ['$limit' => 100]
];

$result = $collection->aggregate($pipeline);
?>
```

---

## 🐛 DEBUGGING PERFORMANCE

### Check Query Execution Time

```php
<?php
$start = microtime(true);

// Your query here
$result = $collection->find($filter)->toArray();

$time = (microtime(true) - $start) * 1000;
echo "Query took: {$time}ms\n";

if ($time > 500) {
    echo "⚠️  SLOW QUERY - Consider optimization\n";
}
?>
```

### Monitor Cache Hit Rate

```php
<?php
require_once 'includes/cache.php';

// Get cache statistics
$stats = ResponseCache::getStats();

echo "Cache Status:\n";
echo "  Files: " . $stats['file_count'] . "\n";
echo "  Size: " . $stats['total_size_mb'] . " MB\n";

// Calculate hit rate
if ($hits > 0 || $misses > 0) {
    $hit_rate = $hits / ($hits + $misses) * 100;
    echo "  Hit Rate: $hit_rate%\n";
}
?>
```

### Check Index Usage

```php
<?php
// Get index information
$indexes = $collection->listIndexes();
foreach ($indexes as $index) {
    echo "Index: " . json_encode($index['key']) . "\n";
}

// Test if index is being used
$explain = $collection->aggregate([
    ['$match' => ['status' => 'active']],
], ['explain' => true]);

echo "Execution Plan: " . json_encode($explain, JSON_PRETTY_PRINT);
?>
```

---

## 📋 CODE REVIEW CHECKLIST

When reviewing code, check for:

- [ ] **Projections**: All queries using `find()` have projections
- [ ] **N+1 Prevention**: No queries in loops
- [ ] **Caching**: Expensive operations are cached
- [ ] **Indexing**: Filtered queries have indexes
- [ ] **Limit**: Large result sets are paginated
- [ ] **Sort**: Sorts on indexed fields
- [ ] **Error Handling**: Graceful degradation on cache miss
- [ ] **Timeouts**: Long queries have reasonable timeouts

---

## 🚀 DEPLOYMENT CHECKLIST

Before deploying optimizations:

- [ ] All tests passing
- [ ] Performance benchmarks met
- [ ] Cache directory writable
- [ ] Indexes created in production DB
- [ ] Error logs monitored
- [ ] Rollback plan ready
- [ ] Team notified of changes

---

## 📞 COMMON ISSUES & SOLUTIONS

### Issue: Cache Not Working
```php
// Check if cache directory is writable
$stats = ResponseCache::getStats();
if (!is_writable($stats['directory'])) {
    chmod($stats['directory'], 0755);
}

// Disable cache for debugging
ResponseCache::disable();
```

### Issue: N+1 Query Warnings
```php
// Replace loop with aggregation or batch fetch
// See: Pattern 2 above
```

### Issue: Slow Queries
```php
// Add projections to limit fields
// Add indexes on filter fields
// Use aggregation instead of map-reduce
```

### Issue: Large Response Size
```php
// Add/fix projections
// Implement pagination
// Use gzip compression
```

---

## 🔗 RELATED DOCUMENTATION

- [COMPREHENSIVE_PERFORMANCE_ANALYSIS.md](COMPREHENSIVE_PERFORMANCE_ANALYSIS.md) - Full analysis
- [IMPLEMENTATION_PLAN.md](IMPLEMENTATION_PLAN.md) - Implementation details
- [TEST_CASES_AND_VALIDATION.md](TEST_CASES_AND_VALIDATION.md) - Test suite
- [PERFORMANCE_OPTIMIZATION_REPORT.md](PERFORMANCE_OPTIMIZATION_REPORT.md) - Final report

---

## 🎯 QUICK STATS

**System Improvement Summary:**

```
Before:  3-5 seconds page load  ❌
After:   0.5-1.5 seconds       ✅ 75-85% FASTER

Before:  300-500KB responses    ❌
After:   50-100KB responses     ✅ 75% SMALLER

Before:  20+ queries per page   ❌
After:   < 5 queries            ✅ 80% FEWER

Before:  0% cache hit rate      ❌
After:   > 80% cache hit rate   ✅ NEAR INSTANT
```

---

**Remember:** Fast code is clean code. Always profile before optimizing! 🚀
