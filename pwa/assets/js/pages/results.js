/**
 * Results Page JavaScript
 * Specific functionality for admin/results.php
 */

// Results DataTable
let resultsTable = null;

// Initialize Results Page
function initializeResultsPage() {
    initializeResultsTable();
    setupResultsFilters();
}

// Initialize DataTable for Results
function initializeResultsTable() {
    const resultsTableElement = document.getElementById('resultsTable');
    if (!resultsTableElement || typeof $ === 'undefined') return;
    
    resultsTable = $('#resultsTable').DataTable({
        responsive: true,
        paging: true,
        pageLength: 10,
        ordering: true,
        searching: true,
        language: {
            search: 'Filter results:',
            lengthMenu: 'Show _MENU_ results per page',
            info: 'Showing _START_ to _END_ of _TOTAL_ results'
        },
        dom: '<"row"<"col-md-6"l><"col-md-6"f>>t<"row"<"col-md-6"i><"col-md-6"p>>'
    });
}

// Setup Results Filters
function setupResultsFilters() {
    const filterButton = document.getElementById('filterButton');
    if (filterButton) {
        filterButton.addEventListener('click', applyResultsFilters);
    }
    
    // Setup reset filter button
    const resetButton = document.getElementById('resetFilterButton');
    if (resetButton) {
        resetButton.addEventListener('click', resetResultsFilters);
    }
}

// Apply Results Filters
function applyResultsFilters() {
    const teacherId = document.getElementById('teacherFilter')?.value;
    const startDate = document.getElementById('startDateFilter')?.value;
    const endDate = document.getElementById('endDateFilter')?.value;
    const minRating = document.getElementById('minRatingFilter')?.value;
    
    const filters = {
        teacher_id: teacherId || '',
        start_date: startDate || '',
        end_date: endDate || '',
        min_rating: minRating || ''
    };
    
    loadResultsData(filters);
}

// Reset Results Filters
function resetResultsFilters() {
    document.getElementById('teacherFilter').value = '';
    document.getElementById('startDateFilter').value = '';
    document.getElementById('endDateFilter').value = '';
    document.getElementById('minRatingFilter').value = '';
    
    loadResultsData({});
}

// Load Results Data
function loadResultsData(filters = {}) {
    const queryString = new URLSearchParams(filters).toString();
    const url = `/teacher-eval/admin/results.php?load_data=1&${queryString}`;
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success && resultsTable) {
                resultsTable.clear();
                if (Array.isArray(data.results)) {
                    data.results.forEach(result => {
                        resultsTable.row.add([
                            result.teacher_name || 'N/A',
                            result.evaluation_date || 'N/A',
                            `<span class="badge bg-success">${result.average_rating || 'N/A'}/5</span>`,
                            `<button class="btn btn-sm btn-outline-primary" onclick="viewEvaluationDetails(${result.id})">View</button>`
                        ]);
                    });
                }
                resultsTable.draw();
            }
        })
        .catch(error => {
            console.error('Error loading results:', error);
        });
}

// View Evaluation Details
function viewEvaluationDetails(evaluationId) {
    fetch(`/teacher-eval/api/evaluations.php?id=${evaluationId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayEvaluationModal(data.evaluation);
            }
        });
}

// Display Evaluation Modal
function displayEvaluationModal(evaluation) {
    const modal = document.getElementById('evalDetailsModal');
    if (!modal) return;
    
    // Populate modal content
    const content = document.querySelector('.modal-body');
    if (content) {
        let html = `
            <div class="eval-details-item">
                <div class="eval-details-label">Teacher</div>
                <div class="eval-details-value">${escapeHtml(evaluation.teacher_name)}</div>
            </div>
            <div class="eval-details-item">
                <div class="eval-details-label">Date</div>
                <div class="eval-details-value">${escapeHtml(evaluation.evaluation_date)}</div>
            </div>
            <div class="eval-details-item">
                <div class="eval-details-label">Rating</div>
                <div class="eval-details-value">${evaluation.average_rating}/5</div>
            </div>
        `;
        
        if (evaluation.comments) {
            html += `
                <div class="eval-details-item">
                    <div class="eval-details-label">Comments</div>
                    <div class="eval-details-value">${escapeHtml(evaluation.comments)}</div>
                </div>
            `;
        }
        
        content.innerHTML = html;
    }
    
    // Show modal
    if (typeof bootstrap !== 'undefined') {
        const bsModal = new bootstrap.Modal(modal);
        bsModal.show();
    }
}

// Export Results
function exportResults() {
    const format = document.getElementById('exportFormat')?.value || 'pdf';
    const teacherId = document.getElementById('teacherFilter')?.value || '';
    const startDate = document.getElementById('startDateFilter')?.value || '';
    const endDate = document.getElementById('endDateFilter')?.value || '';
    
    const queryString = new URLSearchParams({
        export: format,
        teacher_id: teacherId,
        start_date: startDate,
        end_date: endDate
    }).toString();
    
    window.location.href = `/teacher-eval/admin/results.php?${queryString}`;
}

// Print Results
function printResults() {
    window.print();
}

// Initialize on DOM Ready
document.addEventListener('DOMContentLoaded', initializeResultsPage);
