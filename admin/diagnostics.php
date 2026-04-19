<?php
/**
 * Path Diagnostics Page
 * Visit this page to verify path detection is working
 */

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/helpers.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Path Diagnostics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dark-theme.css">
    <style>
        body { background: #f5f5f5; padding: 20px; }
        .card { margin: 20px 0; }
        code { background: #f0f0f0; padding: 2px 8px; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mt-4">🔍 Path Diagnostics</h1>
        <p class="text-muted">This page verifies your path detection is working correctly.</p>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5>Server Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>SCRIPT_NAME:</strong></td>
                        <td><code><?php echo $_SERVER['SCRIPT_NAME']; ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>REQUEST_URI:</strong></td>
                        <td><code><?php echo $_SERVER['REQUEST_URI']; ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>DOCUMENT_ROOT:</strong></td>
                        <td><code><?php echo $_SERVER['DOCUMENT_ROOT']; ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>PHP_SELF:</strong></td>
                        <td><code><?php echo $_SERVER['PHP_SELF']; ?></code></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-success text-white">
                <h5>Path Detection Results</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <td><strong>getBasePath():</strong></td>
                        <td><code><?php echo getBasePath(); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>assetPath('css/admin.css'):</strong></td>
                        <td><code><?php echo assetPath('css/admin.css'); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>adminPath('login.php'):</strong></td>
                        <td><code><?php echo adminPath('login.php'); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>adminPath('logout.php'):</strong></td>
                        <td><code><?php echo adminPath('logout.php'); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong>apiPath('questions.php'):</strong></td>
                        <td><code><?php echo apiPath('questions.php'); ?></code></td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-info text-white">
                <h5>Quick Links (for testing)</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="<?php echo adminPath('login.php'); ?>" class="list-group-item list-group-item-action">
                        📝 Go to Login
                    </a>
                    <a href="<?php echo adminPath('logout.php'); ?>" class="list-group-item list-group-item-action">
                        🚪 Test Logout (you must be logged in first)
                    </a>
                    <a href="<?php echo adminPath('dashboard.php'); ?>" class="list-group-item list-group-item-action">
                        📊 Go to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <div class="alert alert-info mt-4">
            <h6>📋 How to Use:</h6>
            <ol>
                <li>Check the "Path Detection Results" section above</li>
                <li>If paths look correct, try logging in and clicking "Logout"</li>
                <li>If paths look wrong, you may need to set <code>APP_BASE_PATH</code> manually</li>
                <li>Open browser DevTools (F12) and check Network tab if still having issues</li>
            </ol>
        </div>

        <div class="alert alert-warning mt-4">
            <h6>⚠️ If your paths don't look right:</h6>
            <p>Edit <code>config/constants.php</code> and add this at the top:</p>
            <code style="display: block; background: #f0f0f0; padding: 10px; margin-top: 10px;">
define('APP_BASE_PATH', '/teacher-eval');  // or '' if in root
            </code>
        </div>
    </div>
</body>
</html>

