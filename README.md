# Laravel Updraft

A Laravel package to easily manage application updates through versioned update packages.

## Installation

You can install the package via composer:

```bash
composer require infinitietechnologies/laravel-updraft
```

After installing the package, publish the configuration file using:

```bash
php artisan vendor:publish --tag=laravel-updraft-config
```

This will create a `laravel-updraft.php` configuration file in your `config` directory.

You may also want to publish the migrations to customize them before running:

```bash
php artisan vendor:publish --tag=laravel-updraft-migrations
```

Or publish the views to customize the look and feel:

```bash
php artisan vendor:publish --tag=laravel-updraft-views
```

## Configuration

The published configuration file contains the following settings:

- `web_interface`: Enable or disable the web interface for the updater
- `middleware`: The middleware to apply to the web routes
- `update_path`: The path where update packages will be stored
- `backup_path`: The path where backups will be stored
- `backup_retention`: The number of backups to keep (0 to keep all)
- `layout`: The blade layout to use for the updraft views (defaults to 'layouts.app')
- `verify_updates`: Enable or disable verification of update packages
- `update_public_key`: The public key used to verify update packages

## Usage

### Creating Update Packages

Update packages are ZIP files with the following structure:

```
update-package.zip/
├── files/                  # Contains all new or modified files with their full path structure
├── migrations/             # New database migrations
├── config/                 # Configuration files to update
├── manifests/              # Separate manifest files
│   ├── file-manifest.json  # Lists all file changes
│   ├── migration-manifest.json  # Lists migrations to run
│   ├── config-manifest.json  # Lists config files to update
│   └── command-manifest.json  # Lists commands to run after update
└── update-manifest.json    # Main manifest with core metadata
```

#### Main Manifest (update-manifest.json)

```json
{
  "name": "Application Update",
  "version": "1.2.0",
  "description": "This update adds new features and fixes bugs",
  "minimumRequiredVersion": "1.1.0",
  "requiredPhpVersion": ">=8.1",
  "requiredLaravelVersion": ">=10.0"
}
```

#### File Manifest (manifests/file-manifest.json)

```json
{
  "added": [
    "app/Models/NewFeature.php",
    "resources/views/new-feature/index.blade.php"
  ],
  "modified": [
    "app/Http/Controllers/ExistingController.php",
    "resources/views/layouts/app.blade.php"
  ],
  "deleted": [
    "app/Models/DeprecatedModel.php"
  ]
}
```

#### Migration Manifest (manifests/migration-manifest.json)

```json
{
  "migrations": [
    "2025_05_08_000000_add_new_feature_table.php"
  ]
}
```

#### Config Manifest (manifests/config-manifest.json)

```json
{
  "configFiles": [
    "new-feature.php"
  ]
}
```

#### Command Manifest (manifests/command-manifest.json)

```json
{
  "postUpdateCommands": [
    "php artisan cache:clear",
    "php artisan config:cache",
    "php artisan view:cache"
  ]
}
```

### Applying Updates

#### Via Web Interface

1. Navigate to `/admin/updates` in your Laravel application
2. Upload the update package using the provided form
3. The system will process the update and display the results

#### Via Artisan Command

```bash
php artisan updraft:update path/to/update-package.zip
```

or to skip confirmation:

```bash
php artisan updraft:update path/to/update-package.zip --force
```

### Rolling Back Updates

If you need to revert to a previous version, Laravel Updraft provides rollback functionality.

#### Via Web Interface

1. Navigate to `/admin/updates/rollback` in your Laravel application
2. Select the version you want to roll back to
3. Confirm the rollback operation

Alternatively, you can access the rollback options from the update history page.

#### Via Artisan Command

```bash
php artisan updraft:rollback
```

This will list available backups and prompt you to select one. Or specify a backup ID directly:

```bash
php artisan updraft:rollback {backupId}
```

Use the `--force` option to skip confirmation:

```bash
php artisan updraft:rollback {backupId} --force
```

## Update Process

When an update is applied, the system performs the following steps:

1. Extracts the update package
2. Validates the package structure and manifests
3. Checks version compatibility
4. Creates a backup of files that will be modified or deleted
5. Processes file changes (adds, modifies, and deletes files)
6. Runs any new migrations
7. Updates configuration files
8. Runs post-update commands

If the update fails at any step, the system will attempt to restore from backup.

## Rollback Process

When rolling back to a previous version, the system performs these steps:

1. Creates a safety backup of the current state
2. Restores files from the selected backup
3. Updates the application history to record the rollback

Note: Database changes cannot be automatically reverted during rollback. Make sure you have a database backup if needed.

## Customizing the UI

Laravel Updraft views use a layout file that can be configured in the `laravel-updraft.php` config file:

```php
'layout' => 'layouts.app',
```

Change this to use your own layout file. For further customization, publish the views:

```bash
php artisan vendor:publish --tag=laravel-updraft-views
```

## Features

- **Simple Update Process**: Streamlined workflow for applying updates to your Laravel application
- **Version Control**: Ensures that updates are only applied to compatible application versions
- **Automatic Backups**: Creates backups before applying updates for safe rollbacks
- **Rollback Capability**: Revert to previous versions when needed
- **Web Interface**: User-friendly interface for uploading, applying, and rolling back updates
- **Command Line Support**: Apply updates and rollbacks via Artisan commands for automated deployment
- **Flexible Manifest System**: Detailed manifests for controlling update behavior
- **Customizable UI**: Configure the layout and publish views to match your application's design
- **Security First**: Package verification and authentication built-in

## Security

- All update operations require authentication and authorization
- Update packages can be verified with a digital signature
- Backups are created before any changes are applied
- Safety backups are created before rollbacks

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Credits

- [Infinitie Technologies](https://github.com/Infinitietechnologies)
- [All Contributors](../../contributors)

## License

This package is open-sourced software licensed under the MIT license.