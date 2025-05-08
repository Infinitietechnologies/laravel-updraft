/**
 * Laravel Updraft - Rollback Handler Module
 * Handles rollback confirmation and AJAX submission
 */

const RollbackHandler = (function() {
    // Private variables
    let formElement = null;
    let confirmBtn = null;
    
    /**
     * Initialize the rollback handler
     * @param {Object} options Configuration options
     */
    function init(options = {}) {
        const defaults = {
            formId: 'rollbackForm',
            confirmBtnId: 'confirmBtn',
            redirectUrl: '',
            csrfToken: ''
        };
        
        // Merge default options with provided options
        const settings = Object.assign({}, defaults, options);
        
        // Get DOM elements
        formElement = document.getElementById(settings.formId);
        confirmBtn = document.getElementById(settings.confirmBtnId);
        
        if (formElement) {
            setupFormHandler(settings);
        }
    }
    
    /**
     * Set up the form submission handler
     * @param {Object} settings Configuration settings
     */
    function setupFormHandler(settings) {
        formElement.addEventListener('submit', function(e) {
            e.preventDefault();
            
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            
            // Create a new FormData instance from the form
            const formData = new FormData(formElement);
            
            // Create a new XMLHttpRequest
            const xhr = new XMLHttpRequest();
            xhr.open('POST', formElement.action, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            
            // Set up event handlers
            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 400) {
                    // Success - redirect to history page
                    window.location.href = settings.redirectUrl || formElement.dataset.redirectUrl;
                } else {
                    // Error
                    confirmBtn.disabled = false;
                    confirmBtn.innerHTML = '<i class="fas fa-undo me-1"></i> Confirm Rollback';
                    
                    let errorMessage = 'An error occurred during rollback.';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response && response.message) {
                            errorMessage = response.message;
                        }
                    } catch (e) {
                        // Use default error message
                    }
                    
                    // Display error message
                    showError(errorMessage);
                }
            };
            
            xhr.onerror = function() {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fas fa-undo me-1"></i> Confirm Rollback';
                showError('Connection error occurred.');
            };
            
            // Send the request
            xhr.send(formData);
        });
    }
    
    /**
     * Display an error message
     * @param {string} message The error message to display
     */
    function showError(message) {
        // Remove any existing error alerts
        const existingAlerts = document.querySelectorAll('.card-body .alert-danger');
        existingAlerts.forEach(alert => alert.remove());
        
        // Create and show error alert
        const errorAlert = document.createElement('div');
        errorAlert.className = 'alert alert-danger alert-dismissible fade show';
        errorAlert.role = 'alert';
        errorAlert.innerHTML = message + 
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        
        // Insert at the top of the card body
        const cardBody = document.querySelector('.card-body');
        cardBody.insertBefore(errorAlert, cardBody.firstChild);
    }
    
    // Public API
    return {
        init: init
    };
})();

// Export the module
if (typeof module !== 'undefined' && module.exports) {
    module.exports = RollbackHandler;
}