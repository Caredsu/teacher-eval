<?php
/**
 * Admin Navigation Bar
 */

if (!defined('HELPERS_LOADED')) {
    require_once __DIR__ . '/helpers.php';
    define('HELPERS_LOADED', true);
}

if (session_status() === PHP_SESSION_NONE) {
    initializeSession();
}
?>

<style>
    .navbar-dark-theme {
        background: #1e2a3a !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        padding: 0.75rem 1rem !important;
        height: 70px !important;
        min-height: 70px !important;
        max-height: 70px !important;
        display: flex !important;
        align-items: center !important;
        flex-wrap: nowrap !important;
    }
    
    .navbar-dark-theme .navbar-brand {
        color: #fff !important;
        font-size: 18px;
        font-weight: 600;
        margin: 0 !important;
        padding: 0 !important;
        white-space: nowrap;
        flex-shrink: 0 !important;
    }
    
    .navbar-dark-theme .container-fluid {
        display: flex !important;
        align-items: center !important;
        padding: 0 !important;
        height: 100% !important;
        margin: 0 !important;
    }
    
    .navbar-dark-theme .nav-link {
        color: #ecf0f1 !important;
        transition: color 0.2s, text-shadow 0.2s;
        font-weight: 500;
        padding: 0.5rem 1rem !important;
        white-space: nowrap;
        font-size: 15px;
        display: flex !important;
        align-items: center !important;
        gap: 0.5rem;
        height: 100% !important;
        line-height: 1 !important;
        margin: 0 !important;
    }
    
    .navbar-dark-theme .nav-link i {
        display: inline-block !important;
        font-size: 1.1em !important;
        color: #ecf0f1 !important;
    }
    
    .navbar-dark-theme .nav-link:hover {
        color: #3498db !important;
        text-shadow: 0 0 5px rgba(52, 152, 219, 0.5);
    }
    
    .navbar-dark-theme .nav-link:hover i {
        color: #3498db !important;
    }
    
    .navbar-dark-theme .dropdown-menu {
        background: #2c3e50 !important;
        border: 1px solid #475569 !important;
    }
    
    .navbar-dark-theme .dropdown-item {
        color: #bdc3c7 !important;
    }
    
    .navbar-dark-theme .dropdown-item:hover {
        background: #34495e !important;
        color: #3498db !important;
    }
    
    .navbar-toggler {
        padding: 0.25rem 0.5rem !important;
        border: none !important;
    }
    
    .navbar-toggler {
        padding: 0.25rem 0.5rem;
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark navbar-dark-theme">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand fw-bold" href="/teacher-eval/admin/dashboard.php">
            <i class="bi bi-mortarboard"></i> Teacher Evaluation System
        </a>
        
        <!-- Toggler for mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation Items -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/teacher-eval/admin/dashboard.php">
                        <i class="bi bi-graph-up"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/teacher-eval/admin/teachers.php">
                        <i class="bi bi-people"></i> Teachers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/teacher-eval/admin/questions.php">
                        <i class="bi bi-question-circle"></i> Questions
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/teacher-eval/admin/results.php">
                        <i class="bi bi-bar-chart"></i> Results
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/teacher-eval/admin/analytics.php">
                        <i class="bi bi-graph-up"></i> Analytics
                    </a>
                </li>
                <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/teacher-eval/admin/users.php">
                        <i class="bi bi-shield-lock"></i> Users
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= escapeOutput($_SESSION['admin_username'] ?? 'Admin') ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="/teacher-eval/admin/settings.php"><i class="bi bi-gear"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form method="POST" action="/teacher-eval/admin/logout.php" class="d-inline">
                                <button type="submit" class="dropdown-item"><i class="bi bi-box-arrow-right"></i> Logout</button>
                            </form>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
