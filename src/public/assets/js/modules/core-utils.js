/**
 * Core Utilities Module
 * Provides essential utility functions used across the Laravel Updraft application
 */

const CoreUtils = (function() {
    'use strict';

    // Cache DOM elements for reuse
    const cache = {
        routes: {}
    };
    
    /**
     * Initialize the core utilities
     * @public
     */
    function init() {
        cacheRoutes();
        setupCSRFToken();
    }

    /**
     * Cache route URLs from data attributes for easy access
     * @private
     */
    function cacheRoutes() {
        document.querySelectorAll('[data-route]').forEach(element => {
            const routeName = element.getAttribute('data-route');
            const routeUrl = element.getAttribute('data-url');
            if (routeName && routeUrl) {
                cache.routes[routeName] = routeUrl;
            }
        });
    }
    
    /**
     * Set up CSRF token for all AJAX requests
     * @private
     */
    function setupCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        if (token) {
            // Add CSRF token to all AJAX requests
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': token
                }
            });
        }
    }

    /**
     * Get a route URL by name
     * @public
     * @param {string} name - Route name
     * @returns {string|null} - Route URL or null if not found
     */
    function getRoute(name) {
        return cache.routes[name] || null;
    }
    
    /**
     * Show an alert message
     * @public
     * @param {string} message - The message to display
     * @param {string} type - Alert type: success, info, warning, danger
     * @param {HTMLElement} container - Container to insert the alert
     * @param {boolean} dismissible - Whether the alert should be dismissible
     * @param {number} [timeout=5000] - Auto-dismiss timeout in ms, 0 for no auto-dismiss
     */
    function showAlert(message, type, container, dismissible = true, timeout = 5000) {
        if (!container || !message) return;
        
        // Create alert element
        const alertEl = document.createElement('div');
        alertEl.className = `alert alert-${type || 'info'} ${dismissible ? 'alert-dismissible fade show' : ''}`;
        alertEl.role = 'alert';
        
        // Set alert content
        alertEl.innerHTML = message;
        
        // Add dismiss button if dismissible
        if (dismissible) {
            alertEl.innerHTML += `
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
        }
        
        // Insert at the beginning of the container
        if (container.firstChild) {
            container.insertBefore(alertEl, container.firstChild);
        } else {
            container.appendChild(alertEl);
        }
        
        // Auto-dismiss if timeout is set
        if (timeout > 0) {
            setTimeout(() => {
                if (alertEl.parentNode) {
                    const bsAlert = new bootstrap.Alert(alertEl);
                    bsAlert.close();
                }
            }, timeout);
        }
    }
    
    /**
     * Format a date string to a human-readable format
     * @public
     * @param {string} dateString - ISO date string
     * @param {boolean} includeTime - Whether to include time
     * @returns {string} - Formatted date string
     */
    function formatDate(dateString, includeTime = true) {
        if (!dateString) return 'N/A';
        
        try {
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
                options.second = '2-digit';
            }
            
            return date.toLocaleDateString(undefined, options);
        } catch (e) {
            return dateString;
        }
    }
    
    /**
     * Safely parse JSON from a string
     * @public
     * @param {string} jsonString - JSON string to parse
     * @param {*} fallback - Fallback value if parsing fails
     * @returns {*} - Parsed object or fallback value
     */
    function safeParseJSON(jsonString, fallback = {}) {
        try {
            return JSON.parse(jsonString);
        } catch (e) {
            console.error('Failed to parse JSON', e);
            return fallback;
        }
    }
    
    /**
     * Debounce a function call
     * @public
     * @param {Function} func - Function to debounce
     * @param {number} wait - Wait time in milliseconds
     * @returns {Function} - Debounced function
     */
    function debounce(func, wait = 300) {
        let timeout;
        
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Public API
    return {
        init,
        getRoute,
        showAlert,
        formatDate,
        safeParseJSON,
        debounce
    };
})();

// Auto-initialize if document is already loaded
if (document.readyState === 'complete' || document.readyState === 'interactive') {
    CoreUtils.init();
} else {
    document.addEventListener('DOMContentLoaded', CoreUtils.init);
}

// Export module
window.CoreUtils = CoreUtils;