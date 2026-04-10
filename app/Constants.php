<?php
/**
 * Application Constants
 * Central configuration for the application
 */

define('APP_NAME', 'Teacher Evaluation System');
define('APP_VERSION', '2.0.0');

// Database
define('DB_NAME', 'teacher_evaluation');
define('DB_HOST', '127.0.0.1');  // Force localhost - MUST BE THIS VALUE
define('DB_PORT', 27017);

// Paths
define('APP_ROOT', dirname(dirname(__FILE__)));
define('APP_PATH', APP_ROOT . '/app');
define('CONFIG_PATH', APP_ROOT . '/config');
define('RESOURCES_PATH', APP_ROOT . '/resources');
define('ASSETS_PATH', APP_ROOT . '/assets');
define('STORAGE_PATH', APP_ROOT . '/storage');

// URLs
define('BASE_URL', '/teacher-eval');
define('ADMIN_URL', BASE_URL . '/admin');
define('STUDENT_URL', BASE_URL . '/student');
define('ASSETS_URL', BASE_URL . '/assets');

// Session timeout (in seconds)
define('SESSION_TIMEOUT', 1800);

// Password requirements
define('MIN_PASSWORD_LENGTH', 6);

// Pagination
define('ITEMS_PER_PAGE', 50);

// Enable/Disable debug mode
define('DEBUG_MODE', false);

// Security
define('CSRF_TOKEN_NAME', 'csrf_token');
define('REMEMBER_ME_COOKIE', 'teacher_eval_remember');

// MongoDB Collections
define('COLLECTION_ADMINS', 'admins');
define('COLLECTION_TEACHERS', 'teachers');
define('COLLECTION_QUESTIONS', 'questions');
define('COLLECTION_EVALUATIONS', 'evaluations');
define('COLLECTION_ACTIVITY_LOG', 'activity_log');

// ============================================
// ROLE-BASED ACCESS CONTROL (RBAC)
// ============================================

// User Roles
const USER_ROLES = [
    'admin' => 'Administrator',
    'staff' => 'Staff'
];

// Permissions by Role
const ROLE_PERMISSIONS = [
    'admin' => [
        'view_dashboard',
        'manage_users',
        'manage_teachers',
        'manage_questions',
        'manage_evaluations',
        'view_results',
        'export_data',
        'manage_settings',
        'delete_data'
    ],
    'staff' => [
        'view_dashboard',
        'manage_teachers',
        'manage_evaluations',
        'view_results',
        'export_data'
    ]
];

// Role Hierarchy (for inheritance)
const ROLE_HIERARCHY = [
    'admin' => 2,
    'staff' => 1,
    'guest' => 0
];

// ============================================
// LANGUAGE SUPPORT FOR ROLES
// ============================================

// Role Labels in English
const ROLE_LABELS_EN = [
    'admin' => 'Administrator',
    'staff' => 'Staff'
];

// Role Labels in Filipino (Tagalog)
const ROLE_LABELS_TL = [
    'admin' => 'Administrator',
    'staff' => 'Kawani'
];

// Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_STAFF', 'staff');

// HTTP Status Codes
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_SERVER_ERROR', 500);
