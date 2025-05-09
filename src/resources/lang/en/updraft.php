<?php

return [
    // General
    'app_name' => 'Laravel Updraft',
    'toggle_navigation' => 'Toggle navigation',
    'upload_update' => 'Upload Update',
    'rollback' => 'Rollback',
    
    // Update Form
    'upload_update_package' => 'Upload Update Package',
    'select_update_package' => 'Please select the update package (.zip) file to upload.',
    'file_requirements' => 'Only .zip files are accepted. Maximum file size: 50MB.',
    'backup_confirmation' => 'I confirm that I have backed up my application before applying this update.',
    'upload_and_apply' => 'Upload and Apply Update',
    'update_instructions' => 'Update Instructions',
    'instructions' => [
        'trusted_source' => 'Ensure you have downloaded the update package from a trusted source.',
        'backup' => 'Always back up your application and database before applying any updates.',
        'upload' => 'Upload the update package using the form above.',
        'automatic_process' => 'The system will automatically:',
        'extract' => 'Extract the update package',
        'validate' => 'Validate the package structure',
        'backup_files' => 'Create a backup of affected files',
        'apply_changes' => 'Apply file changes',
        'run_migrations' => 'Run any included database migrations',
        'update_config' => 'Update configuration files',
        'run_commands' => 'Run any post-update commands',
        'update_vendor' => 'Update vendor files if included in the package',
        'restore' => 'If the update fails, the system will attempt to restore from backup.',
    ],
    
    // Update History
    'update_history' => 'Update History',
    'rollback_manager' => 'Rollback Manager',
    'details' => 'Details',
    'roll_back' => 'Roll Back',
    'version' => 'Version',
    'name' => 'Name',
    'applied_at' => 'Applied At',
    'status' => 'Status',
    'applied_by' => 'Applied By',
    'actions' => 'Actions',
    'action' => 'Action',
    'successful' => 'Successful',
    'failed' => 'Failed',
    'system' => 'System',
    'description' => 'Description:',
    'backup_id' => 'Backup ID:',
    'roll_back_before' => 'Roll Back to Before This Update',
    'metadata' => 'Metadata:',
    'no_updates' => 'No updates have been applied yet.',
    
    // Rollback
    'rollback_options' => 'Rollback Options',
    'confirm_rollback' => 'Confirm Rollback',
    'confirm_rollback_button' => 'Confirm Rollback',
    'back_to_rollback' => 'Back to Rollback Options',
    'available_versions' => 'Available Versions for Rollback',
    'rollback_description' => 'Select a version to roll back to. This will restore your application to the state it was in before the selected update was applied.',
    'no_updates_for_rollback' => 'No updates available for rollback. Updates must be successfully applied and have a backup available.',
    'warning' => 'Warning',
    'rollback_warning' => 'Rolling back will revert files to their previous state, but database changes cannot be automatically reverted. Make sure you have a database backup before proceeding.',
    'rollback_warning_detailed' => 'You are about to roll back your application to a previous version. This action cannot be undone.',
    'rollback_confirmation_message' => 'You are about to roll back to before the following update was applied:',
    'backup_id_label' => 'Backup ID',
    'unknown' => 'Unknown',
    'important_notes' => 'Important Notes',
    'restore_files' => 'This will restore your files to their previous state.',
    'db_warning' => 'Database changes cannot be automatically reverted. Make sure you have a database backup.',
    'safety_backup' => 'A safety backup of your current state will be created before the rollback.',
    'cancel' => 'Cancel',
    
    // Vendor update
    'vendor_updates' => 'Vendor Updates',
    'vendor_update_description' => 'This update includes changes to vendor files. These will be applied automatically.',
    'vendor_update_warning' => 'Updating vendor files directly may cause issues if you later run composer update. Consider updating dependencies through composer when possible.',

    // Alert messages
    'close' => 'Close',
];
