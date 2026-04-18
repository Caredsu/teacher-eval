<?php
/**
 * Application Constants and Configuration
 */

// ============================================================================
// ENVIRONMENT
// ============================================================================
define('APP_NAME', 'Teacher Evaluation System');
define('APP_VERSION', '1.0.0');
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', APP_ENV === 'development');

// ============================================================================
// DATABASE
// ============================================================================
define('DB_HOST', getenv('DB_HOST') ?: (isset($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : null));
define('DB_PORT', getenv('DB_PORT') ?: (isset($_ENV['DB_PORT']) ? $_ENV['DB_PORT'] : 27017));
define('DB_NAME', getenv('DB_NAME') ?: (isset($_ENV['DB_NAME']) && $_ENV['DB_NAME'] ? $_ENV['DB_NAME'] : 'teacher_eval'));

// ============================================================================
// COLLECTIONS
// ============================================================================
define('COLLECTION_ADMINS', 'admins');
define('COLLECTION_TEACHERS', 'teachers');
define('COLLECTION_QUESTIONS', 'questions');
define('COLLECTION_EVALUATIONS', 'evaluations');
define('COLLECTION_ACTIVITY_LOGS', 'activity_logs');

// ============================================================================
// SESSION
// ============================================================================
define('SESSION_LIFETIME', 7200); // 2 hours in seconds
define('SESSION_TTL', 2592000); // 30 days in seconds for "Remember me"

// ============================================================================
// DEPARTMENTS
// ============================================================================
define('ALLOWED_DEPARTMENTS', ['ECT', 'EDUC', 'CCJE', 'BHT']);

// ============================================================================
// STATUS
// ============================================================================
define('ALLOWED_STATUS', ['active', 'inactive']);
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');

// ============================================================================
// ROLES
// ============================================================================
define('ALLOWED_ROLES', ['admin', 'super_admin']);
define('ROLE_ADMIN', 'admin');
define('ROLE_SUPER_ADMIN', 'super_admin');

// ============================================================================
// RATING SCALE
// ============================================================================
define('MIN_RATING', 1);
define('MAX_RATING', 5);
define('DEFAULT_RATING', 3);

// ============================================================================
// VALIDATION RULES
// ============================================================================
define('MIN_NAME_LENGTH', 2);
define('MAX_NAME_LENGTH', 100);
define('MIN_USERNAME_LENGTH', 3);
define('MAX_USERNAME_LENGTH', 50);
define('MIN_PASSWORD_LENGTH', 6);
define('MIN_QUESTION_LENGTH', 5);
define('MAX_QUESTION_LENGTH', 500);

// ============================================================================
// BCRYPT PASSWORD HASHING
// ============================================================================
define('BCRYPT_COST', 10);

// ============================================================================
// PAGINATION
// ============================================================================
define('ITEMS_PER_PAGE', 20);
define('DEFAULT_LIMIT', 50);
define('MAX_LIMIT', 1000);

// ============================================================================
// ERROR MESSAGES
// ============================================================================
define('ERROR_UNAUTHORIZED', 'Unauthorized access');
define('ERROR_FORBIDDEN', 'You do not have permission to perform this action');
define('ERROR_NOT_FOUND', 'Resource not found');
define('ERROR_INVALID_INPUT', 'Invalid input provided');
define('ERROR_DATABASE', 'Database error occurred');
define('ERROR_CSRF_TOKEN', 'CSRF token mismatch');

// ============================================================================
// SUCCESS MESSAGES
// ============================================================================
define('SUCCESS_CREATED', 'Resource created successfully');
define('SUCCESS_UPDATED', 'Resource updated successfully');
define('SUCCESS_DELETED', 'Resource deleted successfully');
define('SUCCESS_LOGIN', 'Login successful');

// ============================================================================
// ACTIVITY LOG ACTIONS
// ============================================================================
define('ACTION_LOGIN', 'LOGIN');
define('ACTION_LOGOUT', 'LOGOUT');
define('ACTION_LOGIN_FAILED', 'LOGIN_FAILED');
define('ACTION_ADD_TEACHER', 'ADD_TEACHER');
define('ACTION_UPDATE_TEACHER', 'UPDATE_TEACHER');
define('ACTION_DELETE_TEACHER', 'DELETE_TEACHER');
define('ACTION_ADD_QUESTION', 'ADD_QUESTION');
define('ACTION_UPDATE_QUESTION', 'UPDATE_QUESTION');
define('ACTION_DELETE_QUESTION', 'DELETE_QUESTION');
define('ACTION_ADD_USER', 'ADD_USER');
define('ACTION_UPDATE_USER', 'UPDATE_USER');
define('ACTION_DELETE_USER', 'DELETE_USER');
define('ACTION_ERROR', 'ERROR');

// ============================================================================
// HTTP STATUS CODES
// ============================================================================
define('HTTP_OK', 200);
define('HTTP_CREATED', 201);
define('HTTP_BAD_REQUEST', 400);
define('HTTP_UNAUTHORIZED', 401);
define('HTTP_FORBIDDEN', 403);
define('HTTP_NOT_FOUND', 404);
define('HTTP_CONFLICT', 409);
define('HTTP_INTERNAL_ERROR', 500);

// ============================================================================
// UI CONFIGURATION
// ============================================================================
define('THEME_PRIMARY', '#667eea');
define('THEME_SECONDARY', '#764ba2');
define('THEME_SUCCESS', '#48bb78');
define('THEME_DANGER', '#f56565');
define('THEME_WARNING', '#ed8936');
define('THEME_INFO', '#4299e1');

// ============================================================================
// PAGE TITLES
// ============================================================================
define('PAGE_DASHBOARD', 'Dashboard');
define('PAGE_TEACHERS', 'Teachers Management');
define('PAGE_QUESTIONS', 'Questions Management');
define('PAGE_RESULTS', 'Results & Analytics');
define('PAGE_USERS', 'User Management');
define('PAGE_LOGIN', 'Admin Login');
