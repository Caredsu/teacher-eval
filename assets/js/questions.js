/**
 * Questions Management
 */

let questionsTable;

// Function to get CSRF token
function getCSRFToken() {
    return document.querySelector('input[name="csrf_token"]')?.value || '';
}

// Initialize DataTable on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeQuestionsTable();
    setupFormHandlers();
});

/**
 * Initialize Questions DataTable
 */
function initializeQuestionsTable() {
    questionsTable = $('#questionsTable').DataTable({
        processing: true,
        ajax: {
            url: '/teacher-eval/admin/questions.php',
            type: 'POST',
            data: function(d) {
                d = {
                    ajax_action: 'get_questions',
                    csrf_token: getCSRFToken()
                };
                return d;
            },
            dataSrc: function(json) {
                // Return the data array from our custom response
                return json.data || [];
            }
        },
        columns: [
            { data: 'question_order', name: 'question_order', width: '8%' },
            { data: 'question_text', name: 'question_text', render: function(data) {
                return $('<div>').text(data).html(); // Escape HTML
            }},
            { data: 'category', name: 'category', width: '12%' },
            { data: 'status_badge', name: 'status', width: '10%', orderable: false, searchable: false, render: function(data) {
                return data; // Render HTML
            }},
            { data: 'updated_at', name: 'updated_at', width: '12%' },
            { data: 'updated_by', name: 'updated_by', width: '10%' },
            { data: 'actions', name: 'actions', width: '15%', orderable: false, searchable: false, render: function(data) {
                return data; // Render HTML
            }}
        ],
        order: [[0, 'asc']],
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
        responsive: true,
        autoWidth: false,
        language: {
            emptyTable: 'No questions found. Click "Add New Question" to create one.',
            loadingRecords: 'Loading questions...',
            processing: 'Processing...',
            search: '_INPUT_',
            searchPlaceholder: 'Search questions...',
            info: 'Showing _START_ to _END_ of _TOTAL_ questions',
            infoEmpty: 'No questions to display',
            paginate: {
                first: 'First',
                last: 'Last',
                next: 'Next',
                previous: 'Previous'
            }
        }
    });

    // Reinitialize on each draw
    questionsTable.on('draw', function() {
        setupActionButtons();
    });
}

/**
 * Setup action buttons
 */
function setupActionButtons() {
    // Edit buttons
    document.querySelectorAll('.btn-edit-question').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const questionId = this.getAttribute('data-id');
            editQuestion(questionId);
        });
    });

    // Delete buttons
    document.querySelectorAll('.btn-delete-question').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const questionId = this.getAttribute('data-id');
            deleteQuestion(questionId);
        });
    });
}

/**
 * Open modal for adding new question
 */
function openQuestionModal() {
    // Reset form
    document.getElementById('questionForm').reset();
    document.getElementById('questionIdInput').value = '';
    document.getElementById('ajaxActionInput').value = 'add_question';
    document.getElementById('modalTitle').textContent = 'Add New Question';
    document.getElementById('question_status').value = 'active';
    
    // Show modal
    Modal.show('questionModal');
}

/**
 * Edit existing question
 */
function editQuestion(questionId) {
    // Fetch question data
    const formData = new FormData();
    formData.append('ajax_action', 'get_question');
    formData.append('question_id', questionId);
    formData.append('csrf_token', getCSRFToken());

    fetch('/teacher-eval/admin/questions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success && result.data) {
            const question = result.data;
            // Populate form
            document.getElementById('questionIdInput').value = question._id || '';
            document.getElementById('question_order').value = question.question_order || 0;
            document.getElementById('question_text').value = question.question_text || '';
            document.getElementById('question_category').value = question.category || 'General';
            document.getElementById('question_status').value = question.status || 'active';
            document.getElementById('ajaxActionInput').value = 'update_question';
            document.getElementById('modalTitle').textContent = 'Edit Question';
            
            // Show modal
            Modal.show('questionModal');
        } else {
            Toast.error(result.message || 'Failed to load question details');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        Toast.error('An error occurred while loading the question');
    });
}

/**
 * Delete question
 */
function deleteQuestion(questionId) {
    Swal.fire({
        title: 'Delete Question?',
        text: 'This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#667eea',
        confirmButtonText: 'Delete',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('ajax_action', 'delete_question');
            formData.append('question_id', questionId);
            formData.append('csrf_token', getCSRFToken());

            fetch('/teacher-eval/admin/questions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    Toast.success('Question deleted successfully');
                    questionsTable.ajax.reload();
                } else {
                    Toast.error(result.message || 'Failed to delete question');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.error('An error occurred while deleting the question');
            });
        }
    });
}

/**
 * Setup form handlers
 */
function setupFormHandlers() {
    const questionForm = document.getElementById('questionForm');
    
    if (questionForm) {
        questionForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch('/teacher-eval/admin/questions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    Toast.success(result.message || 'Question saved successfully');
                    Modal.hide('questionModal');
                    questionForm.reset();
                    questionsTable.ajax.reload();
                } else {
                    Toast.error(result.message || 'Failed to save question');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Toast.error('An error occurred while saving the question');
            });
        });
    }
}

/**
 * Reload questions table
 */
function reloadQuestionsTable() {
    if (questionsTable) {
        questionsTable.ajax.reload();
    }
}
