<?php
/**
 * Admin Logout
 */

require_once '../includes/helpers.php';
require_once '../config/database.php';

initializeSession();

if (!isLoggedIn()) {
    redirect(BASE_URL . '/admin/login.php');
}

logActivity('LOGOUT', 'Admin logged out');

// Redirect to login with logout flag
session_start();
session_destroy();
header('Location: ' . BASE_URL . '/admin/login.php?logged_out=1');
exit;
?>
