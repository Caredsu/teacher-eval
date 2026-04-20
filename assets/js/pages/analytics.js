/**
 * Analytics Page JavaScript
 * Specific functionality for admin/analytics.php
 */

// Chart References
let analyticsCharts = {};

// Initialize Analytics Charts
function initializeAnalyticsCharts() {
    // Evaluation Status Distribution Chart
    const statusCanvas = document.getElementById('evaluationStatusChart');
    if (statusCanvas && typeof Chart !== 'undefined') {
        const statusCtx = statusCanvas.getContext('2d');
        analyticsCharts.status = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusCanvas.dataset.labels ? JSON.parse(statusCanvas.dataset.labels) : ['Completed', 'Pending'],
                datasets: [{
                    data: statusCanvas.dataset.data ? JSON.parse(statusCanvas.dataset.data) : [0, 0],
                    backgroundColor: ['#00d4ff', '#ffa500'],
                    borderColor: '#ffffff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: '#000000',
                            font: { size: 13 },
                            padding: 15
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }
    
    // Time Series Chart
    const timeSeriesCanvas = document.getElementById('evaluationTimeSeriesChart');
    if (timeSeriesCanvas && typeof Chart !== 'undefined') {
        const timeSeriesCtx = timeSeriesCanvas.getContext('2d');
        analyticsCharts.timeSeries = new Chart(timeSeriesCtx, {
            type: 'line',
            data: {
                labels: timeSeriesCanvas.dataset.labels ? JSON.parse(timeSeriesCanvas.dataset.labels) : [],
                datasets: [{
                    label: 'Evaluations Over Time',
                    data: timeSeriesCanvas.dataset.data ? JSON.parse(timeSeriesCanvas.dataset.data) : [],
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#000000'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#000000'
                        },
                        grid: {
                            color: '#e2e8f0'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#000000'
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    // Department Comparison Chart
    const departmentCanvas = document.getElementById('departmentComparisonChart');
    if (departmentCanvas && typeof Chart !== 'undefined') {
        const departmentCtx = departmentCanvas.getContext('2d');
        analyticsCharts.department = new Chart(departmentCtx, {
            type: 'bar',
            data: {
                labels: departmentCanvas.dataset.labels ? JSON.parse(departmentCanvas.dataset.labels) : [],
                datasets: [{
                    label: 'Evaluations by Department',
                    data: departmentCanvas.dataset.data ? JSON.parse(departmentCanvas.dataset.data) : [],
                    backgroundColor: '#8b5cf6',
                    borderColor: '#7c3aed',
                    borderWidth: 1,
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        labels: {
                            color: '#000000'
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#000000'
                        },
                        grid: {
                            color: '#e2e8f0'
                        }
                    },
                    x: {
                        ticks: {
                            color: '#000000'
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
}

// Filter Analytics Data
function filterAnalyticsData() {
    const startDate = document.getElementById('startDate')?.value;
    const endDate = document.getElementById('endDate')?.value;
    const department = document.getElementById('departmentFilter')?.value;
    
    if (startDate || endDate || department) {
        // Trigger data reload via AJAX
        loadAnalyticsData({ startDate, endDate, department });
    }
}

// Load Analytics Data via AJAX
function loadAnalyticsData(filters = {}) {
    const queryString = new URLSearchParams(filters).toString();
    const url = `/teacher-eval/admin/analytics.php?load_data=1&${queryString}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update charts with new data
                updateAnalyticsCharts(data);
                showSuccess('Analytics Updated', 'Data refreshed successfully', 2000);
            } else {
                showError('Error', data.message || 'Failed to load analytics data');
            }
        })
        .catch(error => {
            console.error('Analytics load error:', error);
            showError('Error', 'Failed to load analytics data');
        });
}

// Update Charts with New Data
function updateAnalyticsCharts(data) {
    if (analyticsCharts.status && data.status) {
        analyticsCharts.status.data.datasets[0].data = data.status;
        analyticsCharts.status.update();
    }
    
    if (analyticsCharts.timeSeries && data.timeSeries) {
        analyticsCharts.timeSeries.data.labels = data.timeSeriesLabels;
        analyticsCharts.timeSeries.data.datasets[0].data = data.timeSeries;
        analyticsCharts.timeSeries.update();
    }
    
    if (analyticsCharts.department && data.department) {
        analyticsCharts.department.data.labels = data.departmentLabels;
        analyticsCharts.department.data.datasets[0].data = data.department;
        analyticsCharts.department.update();
    }
}

// Export Analytics
function exportAnalytics() {
    const format = document.getElementById('exportFormat')?.value || 'pdf';
    const startDate = document.getElementById('startDate')?.value;
    const endDate = document.getElementById('endDate')?.value;
    const department = document.getElementById('departmentFilter')?.value;
    
    const queryString = new URLSearchParams({
        export: format,
        startDate: startDate || '',
        endDate: endDate || '',
        department: department || ''
    }).toString();
    
    window.location.href = `/teacher-eval/admin/analytics.php?${queryString}`;
}

// Initialize on DOM Ready
document.addEventListener('DOMContentLoaded', () => {
    initializeAnalyticsCharts();
    
    // Setup filter event listeners
    const filterElements = [
        'startDate', 'endDate', 'departmentFilter'
    ];
    
    filterElements.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', filterAnalyticsData);
        }
    });
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    Object.values(analyticsCharts).forEach(chart => {
        if (chart) chart.destroy();
    });
});
