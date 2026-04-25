/**
 * Theme Management - Dark/Light Mode Toggle
 */

// Check for saved theme preference or OS preference
function initializeTheme() {
    const savedTheme = localStorage.getItem('theme') || 'light';
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    if (savedTheme === 'dark' || (savedTheme === 'auto' && prefersDark)) {
        document.body.classList.add('dark-mode');
    } else {
        document.body.classList.remove('dark-mode');
    }
}

// Toggle theme function
function toggleTheme() {
    document.body.classList.toggle('dark-mode');
    
    // Save preference
    const isDarkMode = document.body.classList.contains('dark-mode');
    localStorage.setItem('theme', isDarkMode ? 'dark' : 'light');
    
    return isDarkMode;
}

// Set specific theme
function setTheme(theme) {
    if (theme === 'dark') {
        document.body.classList.add('dark-mode');
        localStorage.setItem('theme', 'dark');
    } else if (theme === 'light') {
        document.body.classList.remove('dark-mode');
        localStorage.setItem('theme', 'light');
    }
}

// Get current theme
function getCurrentTheme() {
    return document.body.classList.contains('dark-mode') ? 'dark' : 'light';
}

// Initialize theme on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializeTheme);
} else {
    initializeTheme();
}

// Watch for system theme changes
window.matchMedia('(prefers-color-scheme: dark)').addListener(e => {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'auto' || savedTheme === null) {
        setTheme(e.matches ? 'dark' : 'light');
    }
});
