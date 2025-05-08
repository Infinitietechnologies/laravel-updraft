/**
 * Laravel Updraft - Utilities Module
 * Common utility functions used across the application
 */

const UpdraftUtils = (function() {
    'use strict';
    
    /**
     * Get a CSRF token from the page meta tags
     * @return {string} The CSRF token
     */
    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }
    
    /**
     * Get route URL from the global LaravelUpdraft object or data attributes
     * @param {string} name The route name
     * @return {string} The route URL
     */
    function getRouteUrl(name) {
        // Check if we have the routes in a global object
        if (window.LaravelUpdraft && window.LaravelUpdraft.routes && window.LaravelUpdraft.routes[name]) {
            return window.LaravelUpdraft.routes[name];
        }
        
        // Try to find a route in data attributes
        const routeElement = document.querySelector(`[data-route="${name}"]`);
        if (routeElement) {
            return routeElement.getAttribute('data-url') || '';
        }
        
        // Return the current URL as fallback
        return window.location.href;
    }
    
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
     * Show an alert message in the specified container
     * @param {string} message The message to display
     * @param {string} type The alert type (success, danger, warning, info)
     * @param {string} containerId ID of the container element (defaults to page body)
     * @param {boolean} dismissible Whether the alert should be dismissible
     * @param {number} autoDismiss Time in ms after which the alert auto-dismisses (0 to disable)
     */
    function showAlert(message, type = 'info', containerId = null, dismissible = true, autoDismiss = 0) {
        const container = containerId ? document.getElementById(containerId) : document.body;
        if (!container) return;
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} fade show` + (dismissible ? ' alert-dismissible' : '');
        alertDiv.role = 'alert';
        
        alertDiv.innerHTML = message;
        
        if (dismissible) {
            alertDiv.innerHTML += `
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
        }
        
        container.prepend(alertDiv);
        
        // Initialize the dismiss button if not using Bootstrap JS
        if (dismissible) {
            const closeBtn = alertDiv.querySelector('.btn-close');
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    alertDiv.classList.remove('show');
                    setTimeout(() => alertDiv.remove(), 150);
                });
            }
        }
        
        // Auto-dismiss if specified
        if (autoDismiss > 0) {
            setTimeout(() => {
                alertDiv.classList.remove('show');
                setTimeout(() => alertDiv.remove(), 150);
            }, autoDismiss);
        }
    }
    
    /**
     * Format a date string into a human-friendly format
     * @param {string} dateString The date string to format
     * @param {boolean} includeTime Whether to include the time
     * @return {string} Formatted date string
     */
    function formatDate(dateString, includeTime = true) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return dateString;
        
        const options = {
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        };
        
        if (includeTime) {
            options.hour = '2-digit';
            options.minute = '2-digit';
        }
        
        return new Intl.DateTimeFormat('en-US', options).format(date);
    }
    
    /**
     * Detect the current page based on URL or elements
     * @return {string} The page identifier
     */
    function detectCurrentPage() {
        const path = window.location.pathname;
        
        if (document.getElementById('update_package') && document.getElementById('uploadForm')) {
            return 'update-form';
        }
        
        if (document.getElementById('rollbackForm') && document.getElementById('confirmBtn')) {
            return 'confirm-rollback';
        }
        
        if (document.getElementById('updateHistoryTable')) {
            return 'update-history';
        }
        
        if (document.getElementById('rollbackOptionsContainer')) {
            return 'rollback-options';
        }
        
        return 'unknown';
    }
    
    /**
     * Public API
     */
    return {
        getCsrfToken,
        getRouteUrl,
        initDismissibleAlerts,
        showAlert,
        formatDate,
        detectCurrentPage
    };
})();