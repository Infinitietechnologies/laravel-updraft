/**
 * Updraft - Main JavaScript
 * Handles initialization of modules based on the current page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get the CSRF token from meta tag
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    
    // Detect current page based on URL or page-specific elements
    const currentPath = window.location.pathname;
    
    // Check if we're on the update form page
    if (document.getElementById('update_package') && document.getElementById('uploadForm')) {
        // If FilePondUploader module is available, initialize it
        if (typeof FilePondUploader !== 'undefined') {
            FilePondUploader.init({
                inputId: 'update_package',
                formId: 'uploadForm',
                submitBtnId: 'submitBtn',
                confirmCheckboxId: 'confirm_backup',
                cardBodyId: 'updraftCardBody',
                processUrl: getRouteUrl('updraft.process-file'),
                revertUrl: getRouteUrl('updraft.revert-file'),
                uploadUrl: getRouteUrl('updraft.upload'),
                csrfToken: csrfToken
            });
        }
    }
    
    // Check if we're on the rollback confirmation page
    if (document.getElementById('rollbackForm') && document.getElementById('confirmBtn')) {
        // If RollbackHandler module is available, initialize it
        if (typeof RollbackHandler !== 'undefined') {
            RollbackHandler.init({
                formId: 'rollbackForm',
                confirmBtnId: 'confirmBtn',
                redirectUrl: getRouteUrl('updraft.history') + '?success=1',
                csrfToken: csrfToken
            });
        }
    }
    
    // Initialize dismissible alerts
    initDismissibleAlerts();
});

/**
 * Initialize dismissible alerts functionality
 * This provides fallback for browsers without Bootstrap JS
 */
function initDismissibleAlerts() {
    document.querySelectorAll('.alert-dismissible .btn-close').forEach(button => {
        button.addEventListener('click', () => {
            const alert = button.closest('.alert');
            if (alert) {
                alert.classList.remove('show');
                
                // After the transition completes, remove the element
                setTimeout(() => {
                    alert.remove();
                }, 150);
            }
        });
    });
}

/**
 * Get route URL from the global Updraft object or data attributes
 * @param {string} name The route name
 * @return {string} The route URL
 */
function getRouteUrl(name) {
    // Check if we have the routes in a global object
    if (window.Updraft && window.Updraft.routes && window.Updraft.routes[name]) {
        return window.Updraft.routes[name];
    }
    
    // Try to find a route in data attributes
    const routeElement = document.querySelector(`[data-route="${name}"]`);
    if (routeElement) {
        return routeElement.getAttribute('data-url') || '';
    }
    
    // Return the current URL as fallback
    return window.location.href;
}