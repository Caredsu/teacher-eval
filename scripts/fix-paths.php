#!/usr/bin/env php
<?php
/**
 * Fix Paths Script
 * Updates all hardcoded paths in admin pages to use relative or context-aware paths
 */

$basePath = dirname(__DIR__);
$adminPath = $basePath . '/admin';

$files = [
    'dashboard.php',
    'teachers.php',
    'questions.php',
    'results.php',
    'users.php'
];

foreach ($files as $file) {
    $filePath = $adminPath . '/' . $file;
    
    if (!file_exists($filePath)) {
        echo "⚠️  File not found: $filePath\n";
        continue;
    }
    
    $content = file_get_contents($filePath);
    
    // Replace hardcoded paths with path helper calls
    $original = $content;
    
    // Replace /admin/ paths with adminPath()
    $content = preg_replace_callback(
        '/(href|src)="\/admin\/([^"]+)"/',
        function($matches) {
            return $matches[1] . '="<?php echo adminPath(\'' . $matches[2] . '\'); ?>"';
        },
        $content
    );
    
    // Replace /assets/ paths with assetPath()
    $content = preg_replace_callback(
        '/(href|src)="\/assets\/([^"]+)"/',
        function($matches) {
            return $matches[1] . '="<?php echo assetPath(\'' . $matches[2] . '\'); ?>"';
        },
        $content
    );
    
    // Replace /api/ paths with apiPath()
    $content = preg_replace_callback(
        '/(location\.href|\'\/api\/|"\/api\/)([^"\'\s]+)/',
        function($matches) {
            if (strpos($matches[0], 'location.href') !== false) {
                return 'location.href = "<?php echo apiPath(\'' . str_replace('api/', '', $matches[1]) . $matches[2] . '\'); ?>"';
            }
            return str_replace('/api/', '<?php echo apiPath(\'', $matches[0]) . '\'); ?>';
        },
        $content
    );
    
    if ($content !== $original) {
        file_put_contents($filePath, $content);
        echo "✅ Updated: $file\n";
    } else {
        echo "ℹ️  No changes: $file\n";
    }
}

echo "\n✅ Path fixing complete!\n";
echo "Note: Make sure each file includes helpers.php to use these functions.\n";
?>
