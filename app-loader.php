<?php
/**
 * Dynamic Flutter App Loader
 * Injects the correct assetsUrl based on current request
 */

// Determine the base path
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$basePath = '/teacher-eval';

// Build the full assets URL (always to /teacher-eval/pwa/assets/)
$assetsUrl = "{$protocol}://{$host}{$basePath}/pwa/assets/";

// Read app.html
$appHtml = file_get_contents(__DIR__ . '/app.html');

// Inject a script BEFORE flutter_bootstrap.js to override the assetsUrl
$injectedScript = <<<'JS'
<script>
// Override Flutter build config with correct assetsUrl
window._flutter = window._flutter || {};
window._flutter.buildConfig = {
    "engineRevision":"425cfb54d01a9472b3e81d9e76fd63a4a44cfbcb",
    "builds":[
        {
            "compileTarget":"dart2js",
            "renderer":"canvaskit",
            "mainJsPath":"pwa/main.dart.js",
            "assetsUrl":"JS;

$injectedScript .= json_encode($assetsUrl) . <<<'JS'
        },
        {}
    ]
};
</script>
JS;

// Insert before flutter_bootstrap script
$appHtml = str_replace(
    '<script src="pwa/flutter_bootstrap.js"></script>',
    $injectedScript . "\n<script src=\"pwa/flutter_bootstrap.js\"></script>",
    $appHtml
);

// Remove old buildConfig from flutter_bootstrap.js output
$appHtml = preg_replace(
    '/_flutter\.buildConfig\s*=\s*\{[^}]+\{[^}]+\}[^}]*\};/',
    '',
    $appHtml
);

// Output with correct headers
header('Content-Type: text/html; charset=UTF-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

echo $appHtml;
