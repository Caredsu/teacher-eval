<?php
// Quick verification that everything is ready
session_start();
$_SESSION['admin_id'] = '69e2f5122b9fc6e5880491d2';
$_SESSION['admin_role'] = 'admin';

$_POST['action'] = 'get_users';
ob_start();
require_once __DIR__ . '/api/get-users.php';
$response = ob_get_clean();

$data = json_decode($response, true);
if ($data && $data['success'] && count($data['data']) > 0) {
    echo "✓ Users Page Ready!\n";
    echo "✓ API returns " . count($data['data']) . " users\n";
    echo "✓ Sample: " . $data['data'][0]['username'] . " (" . $data['data'][0]['email'] . ")\n";
    echo "\nYou can now log in and access admin/users.php\n";
} else {
    echo "✗ Issue with API\n";
}
?>
