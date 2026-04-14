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
        position: sticky !important;
        top: 0 !important;
        z-index: 1000 !important;
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
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }
    
    .navbar-dark-theme .dropdown-item {
        color: #000000 !important;
    }
    
    .navbar-dark-theme .dropdown-item:hover {
        background: #f1f5f9 !important;
        color: #8b5cf6 !important;
    }
    
    .navbar-toggler {
        padding: 0.25rem 0.5rem !important;
        border: none !important;
    }
    
    .navbar-toggler {
        padding: 0.25rem 0.5rem;
    }
    
    #notif-badge {
        animation: badgePulse 2s infinite;
    }
    
    @keyframes badgePulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.7; }
    }
</style>

<nav class="navbar navbar-expand-lg navbar-dark navbar-dark-theme">
    <div class="container-fluid">
        <!-- Brand -->
        <a class="navbar-brand fw-bold" href="/teacher-eval/admin/dashboard.php">
            <img src="/teacher-eval/assets/img/2.png" alt="Logo" style="height: 40px; margin-right: 8px; vertical-align: middle;"> Teacher Evaluation System
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
                        <i class="bi bi-bar-chart"></i> Analytics
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/teacher-eval/admin/system-feedback.php">
                        <i class="bi bi-chat-dots"></i> System Feedback
                    </a>
                </li>
                <?php if (isAdmin()): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/teacher-eval/admin/users.php">
                        <i class="bi bi-shield-lock"></i> Users
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" id="notif-link" style="position: relative; cursor: pointer;">
                        <i class="bi bi-bell"></i>
                        <span class="badge bg-danger" id="notif-badge" style="
                            position: absolute;
                            top: 3px;
                            right: -8px;
                            padding: 2px 6px;
                            border-radius: 12px;
                            font-size: 11px;
                            display: none;
                        "></span>
                    </a>
                    <!-- Early script to prevent badge flash on reload -->
                    <script>
                        (function() {
                            const badge = document.getElementById('notif-badge');
                            if (badge) {
                                const lastReadCount = localStorage.getItem('notif_last_read_count');
                                // Always hide by default - show only if there are new notifications
                                badge.style.display = 'none';
                            }
                        })();
                    </script>
                    <!-- Notification Dropdown Panel -->
                    <div id="notif-dropdown" class="notif-dropdown" style="
                        position: absolute;
                        top: 70px;
                        right: 0;
                        background: white;
                        border-radius: 8px;
                        box-shadow: 0 4px 16px rgba(0,0,0,0.15);
                        min-width: 320px;
                        max-height: 400px;
                        display: none;
                        z-index: 1001;
                        overflow-y: auto;
                        overflow-x: hidden;
                    ">
                        <div style="padding: 12px 16px; border-bottom: 1px solid #e0e0e0;">
                            <div style="font-weight: 600; color: #333; font-size: 14px;">
                                <span id="notif-total">0</span> Notifications
                            </div>
                        </div>
                        <div id="notif-list" style="max-height: 280px; overflow-y: auto;">
                            <!-- Notifications will be loaded here -->
                        </div>
                        <div style="padding: 12px 16px; border-top: 1px solid #e0e0e0; text-align: center;">
                            <a href="#" id="clear-notif-btn" style="
                                color: #e74c3c;
                                text-decoration: none;
                                font-size: 13px;
                                font-weight: 500;
                                cursor: pointer;
                            ">Clear Notifications</a>
                        </div>
                    </div>
                </li>
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

<script>
    // Notification dropdown toggle and loader
    const notifLink = document.getElementById('notif-link');
    const notifDropdown = document.getElementById('notif-dropdown');
    const clearNotifBtn = document.getElementById('clear-notif-btn');
    let isClearing = false;
    
    notifLink.addEventListener('click', function(e) {
        e.preventDefault();
        const isOpen = notifDropdown.style.display === 'block';
        
        if (!isOpen) {
            // Opening dropdown - mark as read and save to localStorage
            notifDropdown.style.display = 'block';
            markNotificationsAsRead();
        } else {
            // Closing dropdown
            notifDropdown.style.display = 'none';
        }
    });
    
    // Mark notifications as read (hide badge and save state)
    function markNotificationsAsRead() {
        const badge = document.getElementById('notif-badge');
        badge.style.display = 'none';
        
        // Save to localStorage immediately
        fetch('/teacher-eval/admin/dashboard.php?get_notifications=1')
            .then(response => response.json())
            .then(data => {
                localStorage.setItem('notif_last_read_count', data.count);
            })
            .catch(error => {
                // Even if fetch fails, hide badge
                console.log('Error marking notifications as read:', error.message);
            });
    }
    
    // Clear notifications
    clearNotifBtn.addEventListener('click', function(e) {
        e.preventDefault();
        isClearing = true;
        fetch('/teacher-eval/admin/dashboard.php?clear_notifications=1')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadNotifications();
                    notifDropdown.style.display = 'none';
                    isClearing = false;
                }
            })
            .catch(error => console.log('Clear notifications error:', error.message));
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!notifLink.contains(e.target) && !notifDropdown.contains(e.target)) {
            notifDropdown.style.display = 'none';
        }
    });
    
    // Load notifications
    function loadNotifications() {
        if (isClearing) return; // Don't update while clearing
        
        fetch('/teacher-eval/admin/dashboard.php?get_notifications=1')
            .then(response => response.json())
            .then(data => {
                const badge = document.getElementById('notif-badge');
                const total = document.getElementById('notif-total');
                const list = document.getElementById('notif-list');
                
                // Get last read count from localStorage
                let lastReadCount = localStorage.getItem('notif_last_read_count');
                
                // Initialize on first load - set to current count so nothing shows as unread
                if (lastReadCount === null) {
                    lastReadCount = data.count;
                    localStorage.setItem('notif_last_read_count', data.count);
                } else {
                    lastReadCount = parseInt(lastReadCount);
                }
                
                // Show badge only if current count > last read count AND dropdown is closed
                const hasUnread = data.count > lastReadCount;
                if (hasUnread && notifDropdown.style.display !== 'block') {
                    badge.textContent = data.count > 9 ? '9+' : data.count;
                    badge.style.display = 'inline-block';
                } else {
                    badge.style.display = 'none';
                }
                
                total.textContent = data.count > 0 ? data.count : '0';
                
                // Load notification items
                if (data.notifications && data.notifications.length > 0) {
                    list.innerHTML = data.notifications.map(notif => `
                        <div style="
                            padding: 12px 16px;
                            border-bottom: 1px solid #f0f0f0;
                            display: flex;
                            gap: 12px;
                            align-items: flex-start;
                            cursor: pointer;
                            transition: background-color 0.2s;
                        " onmouseover="this.style.backgroundColor='#f9f9f9'" onmouseout="this.style.backgroundColor='transparent'">
                            <div style="
                                width: 32px;
                                height: 32px;
                                border-radius: 50%;
                                background: #e3f2fd;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                flex-shrink: 0;
                            ">
                                <i class="bi bi-check-circle" style="color: #2196f3; font-size: 16px;"></i>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 500; color: #333; font-size: 13px; margin-bottom: 2px;">
                                    ${notif.title}
                                </div>
                                <div style="color: #999; font-size: 12px;">
                                    ${notif.time}
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    list.innerHTML = '<div style="padding: 20px; text-align: center; color: #999; font-size: 13px;">No notifications yet</div>';
                }
            })
            .catch(error => {
                console.log('Notification load error:', error.message);
                // Hide badge on error to prevent showing stale data
                const badge = document.getElementById('notif-badge');
                badge.style.display = 'none';
            });
    }
    
    // Update on load and every 5 seconds
    document.addEventListener('DOMContentLoaded', loadNotifications);
    setInterval(loadNotifications, 5000);
</script>
