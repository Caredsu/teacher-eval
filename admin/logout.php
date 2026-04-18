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
logout();
?>
