<?php
/**
 * Admin Logout
 */

require_once '../includes/helpers.php';
require_once '../config/database.php';

initializeSession();

if (!isLoggedIn()) {
    redirect('/teacher-eval/admin/login.php');
}

logActivity('LOGOUT', 'Admin logged out');
logout();
?>
