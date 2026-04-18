/**
 * Confirmation Dialogs Helper
 * Used for dashboard, settings, and results delete actions
 */

document.addEventListener('DOMContentLoaded', function() {
    setupDeleteConfirmations();
});

/**
 * Setup delete confirmation dialogs
 */
function setupDeleteConfirmations() {
    // Handle delete links with data attributes
    document.querySelectorAll('[data-confirm-delete]').forEach(element => {
        element.addEventListener('click', function(e) {
            e.preventDefault();
            
            const message = this.getAttribute('data-confirm-delete');
            const url = this.getAttribute('href');
            const name = this.getAttribute('data-item-name') || 'this item';
            
            if (confirm(`${message}\n\nDeleting: ${name}`)) {
                window.location.href = url;
            }
        });
    });

    // Handle delete buttons
    document.querySelectorAll('.btn-delete-confirm').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const message = this.getAttribute('data-message') || 'Are you sure?';
            const href = this.getAttribute('data-href');
            const name = this.getAttribute('data-name') || '';
            
            if (confirm(message + (name ? `\n\nItem: ${name}` : ''))) {
                if (href) {
                    window.location.href = href;
                }
            }
        });
    });

    // Handle SweetAlert2 confirmations if available
    document.querySelectorAll('[data-sweet-confirm]').forEach(element => {
        element.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Confirm Delete?',
                    text: this.getAttribute('data-sweet-confirm'),
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#667eea',
                    confirmButtonText: 'Delete',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = this.getAttribute('href');
                    }
                });
            } else {
                // Fallback to confirm dialog
                if (confirm(this.getAttribute('data-sweet-confirm'))) {
                    window.location.href = this.getAttribute('href');
                }
            }
        });
    });
}

/**
 * Generic confirmation dialog
 */
function confirmDelete(itemName, onConfirm) {
    if (confirm(`Are you sure you want to delete "${itemName}"?`)) {
        if (typeof onConfirm === 'function') {
            onConfirm();
        }
    }
}

/**
 * Confirmation with custom message
 */
function confirmAction(message, onConfirm) {
    if (confirm(message)) {
        if (typeof onConfirm === 'function') {
            onConfirm();
        }
    }
}
