/**
 * Laravel Updraft - FilePond Uploader Module
 * Handles file uploads using FilePond with AJAX submission
 */

const FilePondUploader = (function() {
    // Private variables
    let pond = null;
    let submitBtn = null;
    let confirmCheckbox = null;
    
    /**
     * Initialize the FilePond uploader
     * @param {Object} options Configuration options
     */
    function init(options = {}) {
        const defaults = {
            inputId: 'update_package',
            formId: 'uploadForm',
            submitBtnId: 'submitBtn',
            confirmCheckboxId: 'confirm_backup',
            cardBodyId: 'updraftCardBody',
            maxFileSize: '50MB',
            acceptedFileTypes: ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'],
            processUrl: '',
            revertUrl: '',
            uploadUrl: '',
            csrfToken: ''
        };
        
        // Merge default options with provided options
        const settings = Object.assign({}, defaults, options);
        
        // Get DOM elements
        submitBtn = document.getElementById(settings.submitBtnId);
        confirmCheckbox = document.getElementById(settings.confirmCheckboxId);
        
        // Register FilePond plugins
        registerPlugins();
        
        // Configure and create FilePond instance
        configurePond(settings);
        
        // Set up form submission handler
        setupFormHandler(settings);
    }
    
    /**
     * Register all required FilePond plugins
     */
    function registerPlugins() {
        FilePond.registerPlugin(
            FilePondPluginFileValidateType,
            FilePondPluginFileValidateSize,
            FilePondPluginFilePoster,
            FilePondPluginImagePreview
        );
    }
    
    /**
     * Configure the FilePond instance
     * @param {Object} settings Configuration settings
     */
    function configurePond(settings) {
        // Create the FilePond instance
        pond = FilePond.create(document.getElementById(settings.inputId), {
            labelIdle: 'Drag & Drop your update package or <span class="filepond--label-action">Browse</span>',
            acceptedFileTypes: settings.acceptedFileTypes,
            allowMultiple: false,
            maxFiles: 1,
            maxFileSize: settings.maxFileSize,
            instantUpload: false,
            server: {
                process: {
                    url: settings.processUrl,
                    headers: {
                        'X-CSRF-TOKEN': settings.csrfToken
                    },
                    onerror: (response) => {
                        showError('Error uploading file', settings.cardBodyId);
                        return response;
                    }
                },
                revert: {
                    url: settings.revertUrl,
                    headers: {
                        'X-CSRF-TOKEN': settings.csrfToken
                    }
                },
                restore: null,
                load: null,
                fetch: null
            },
            onaddfile: () => {
                // Enable submit button when a file is added
                if (pond.getFiles().length > 0) {
                    submitBtn.disabled = false;
                }
            },
            onremovefile: () => {
                // Disable submit button when no files are present
                if (pond.getFiles().length === 0) {
                    submitBtn.disabled = true;
                }
            }
        });
        
        // Initially disable the submit button
        submitBtn.disabled = true;
    }
    
    /**
     * Set up the form submission handler
     * @param {Object} settings Configuration settings
     */
    function setupFormHandler(settings) {
        const form = document.getElementById(settings.formId);
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            if (pond.getFiles().length === 0) {
                showError('Please select an update package to upload', settings.cardBodyId);
                return;
            }
            
            if (!confirmCheckbox.checked) {
                showError('Please confirm that you have backed up your application', settings.cardBodyId);
                return;
            }
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            
            // Process the file first if it hasn't been processed yet
            if (pond.getFiles().length > 0 && pond.getFiles()[0].status !== FilePond.FileStatus.PROCESSING_COMPLETE) {
                pond.processFile(pond.getFiles()[0]).then(file => {
                    uploadFile(file, settings);
                }).catch(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Upload and Apply Update';
                    showError('Failed to process file', settings.cardBodyId);
                });
            } else {
                // File already processed, use the serverFileReference
                uploadFile(pond.getFiles()[0], settings);
            }
        });
    }
    
    /**
     * Upload the file using AJAX
     * @param {Object} file FilePond file object
     * @param {Object} settings Configuration settings
     */
    function uploadFile(file, settings) {
        // Create a new FormData instance
        const formData = new FormData();
        formData.append('_token', settings.csrfToken);
        formData.append('confirm_backup', confirmCheckbox.checked ? 1 : 0);
        
        // If we have a server reference, use it, otherwise use the file object
        if (file.serverId) {
            formData.append('update_package', file.serverId);
        } else {
            formData.append('update_package', file.file);
        }
        
        // Create a new XMLHttpRequest
        const xhr = new XMLHttpRequest();
        xhr.open('POST', settings.uploadUrl, true);
        
        // Set up event handlers
        xhr.onload = function() {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Upload and Apply Update';
            
            if (xhr.status >= 200 && xhr.status < 400) {
                // Success
                let response;
                try {
                    response = JSON.parse(xhr.responseText);
                } catch (e) {
                    response = { message: 'Update successfully applied!' };
                }
                
                // Show success message
                const cardBody = document.getElementById(settings.cardBodyId);
                const successAlert = createAlert('success', response.message || 'Update successfully applied!');
                
                // Insert at the top of the card body
                cardBody.insertBefore(successAlert, cardBody.firstChild);
                
                // Reset the form
                pond.removeFiles();
                confirmCheckbox.checked = false;
                submitBtn.disabled = true;
            } else {
                // Error
                let errorMessage = 'An error occurred during upload.';
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    // Use default error message
                }
                
                showError(errorMessage, settings.cardBodyId);
            }
        };
        
        xhr.onerror = function() {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Upload and Apply Update';
            showError('Connection error occurred.', settings.cardBodyId);
        };
        
        // Send the request
        xhr.send(formData);
    }
    
    /**
     * Display an error message
     * @param {string} message The error message to display
     * @param {string} containerId The ID of the container element
     */
    function showError(message, containerId) {
        const container = document.getElementById(containerId);
        
        // Remove any existing error alerts
        const existingAlerts = container.querySelectorAll('.alert-danger');
        existingAlerts.forEach(alert => alert.remove());
        
        // Create and show error alert
        const errorAlert = createAlert('danger', message);
        
        // Insert at the top of the container
        container.insertBefore(errorAlert, container.firstChild);
    }
    
    /**
     * Create an alert element
     * @param {string} type The type of alert (success, danger, warning, etc.)
     * @param {string} message The message to display in the alert
     * @returns {HTMLElement} The created alert element
     */
    function createAlert(type, message) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show`;
        alert.role = 'alert';
        alert.innerHTML = message + 
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        
        return alert;
    }
    
    // Public API
    return {
        init: init
    };
})();

// Export the module
if (typeof module !== 'undefined' && module.exports) {
    module.exports = FilePondUploader;
}