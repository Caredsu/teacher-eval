<?php
/**
 * Admin Layout Template
 * Provides consistent header, navbar, and footer across all admin pages
 */

require_once __DIR__ . '/helpers.php';

function renderAdminLayout($pageTitle = '', $pageContent = '', $pageScripts = '', $extraCss = '') {
    $admin_username = getLoggedInAdminUsername();
    $admin_role = getUserRole();
    $is_super_admin = hasRole('super_admin');
    
    $navbar_items = [
        [
            'label' => 'Dashboard',
            'icon' => 'bi-speedometer2',
            'url' => adminPath('dashboard.php'),
            'roles' => ['admin', 'super_admin']
        ],
        [
            'label' => 'Teachers',
            'icon' => 'bi-people-fill',
            'url' => adminPath('teachers.php'),
            'roles' => ['admin', 'super_admin']
        ],
        [
            'label' => 'Questions',
            'icon' => 'bi-question-circle-fill',
            'url' => adminPath('questions.php'),
            'roles' => ['admin', 'super_admin']
        ],
        [
            'label' => 'Results',
            'icon' => 'bi-bar-chart-fill',
            'url' => adminPath('results.php'),
            'roles' => ['admin', 'super_admin']
        ],
        [
            'label' => 'Users',
            'icon' => 'bi-shield-check-fill',
            'url' => adminPath('users.php'),
            'roles' => ['super_admin']
        ],
    ];
    
    $current_page = basename($_SERVER['REQUEST_URI'], '.php');
    
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo escapeOutput($pageTitle); ?> - <?php echo APP_NAME; ?></title>
        
        <!-- Bootstrap CSS -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        
        <!-- Bootstrap Icons -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
        
        <!-- DataTables -->
        <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
        
        <!-- Chart.js -->
        <link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css" rel="stylesheet">
        
        <!-- Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        
        <!-- Admin Styles -->
        <link href="<?php echo assetPath('css/admin.css'); ?>" rel="stylesheet">
        
        <?php if ($extraCss): echo $extraCss; endif; ?>
        
        <style>
            :root {
                --primary: <?php echo THEME_PRIMARY; ?>;
                --secondary: <?php echo THEME_SECONDARY; ?>;
                --success: <?php echo THEME_SUCCESS; ?>;
                --danger: <?php echo THEME_DANGER; ?>;
                --warning: <?php echo THEME_WARNING; ?>;
                --info: <?php echo THEME_INFO; ?>;
            }
        </style>
    </head>
    <body>
        <!-- Navigation -->
        <nav class="navbar navbar-expand-md navbar-dark" style="background: linear-gradient(135deg, var(--primary), var(--secondary));">
            <div class="container-fluid">
                <a class="navbar-brand fw-bold" href="<?php echo adminPath('dashboard.php'); ?>">
                    <i class="bi bi-mortarboard-fill"></i> <?php echo APP_NAME; ?>
                </a>
                
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav me-auto">
                        <?php foreach ($navbar_items as $item): ?>
                            <?php if (in_array($admin_role, $item['roles'])): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo strpos($_SERVER['REQUEST_URI'], $item['url']) ? 'active' : ''; ?>" 
                                       href="<?php echo $item['url']; ?>">
                                        <i class="bi <?php echo $item['icon']; ?>"></i> <?php echo $item['label']; ?>
                                    </a>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                    
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <span class="navbar-text text-light me-3">
                                <i class="bi bi-person-circle"></i> <?php echo escapeOutput($admin_username); ?> 
                                <span class="badge bg-light text-dark"><?php echo ucfirst(str_replace('_', ' ', $admin_role)); ?></span>
                            </span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-light" href="<?php echo adminPath('logout.php'); ?>">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Main Container -->
        <div class="container-fluid">
            <main class="py-4 px-4">
                    <!-- Flash Messages -->
                    <?php 
                    $error = getErrorMessage();
                    $success = getSuccessMessage();
                    ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-circle"></i> <?php echo escapeOutput($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle"></i> <?php echo escapeOutput($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Page Content -->
                    <?php echo $pageContent; ?>
                </main>
            </div>
        </div>
        
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        
        <!-- DataTables -->
        <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
        <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
        
        <!-- Chart.js -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
        
        <!-- Admin Scripts -->
        <script src="<?php echo assetPath('js/main.js'); ?>"></script>
        
        <?php if ($pageScripts): echo $pageScripts; endif; ?>
    </body>
    </html>
    <?php
}

/**
 * Start buffering for easier layout rendering
 */
function startPageBuffer() {
    ob_start();
}

/**
 * Get buffered content and render with layout
 */
function renderPage($pageTitle = '', $pageScripts = '', $extraCss = '') {
    $pageContent = ob_get_clean();
    renderAdminLayout($pageTitle, $pageContent, $pageScripts, $extraCss);
}
