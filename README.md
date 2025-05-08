# Laravel Updraft

A Laravel package to easily manage application updates through versioned update packages.

## Installation

You can install the package via composer:

```bash
composer require espionage/laravel-updraft
```

After installing the package, publish the configuration file using:

```bash
php artisan vendor:publish --tag=project-updater-config
```

This will create a `project-updater.php` configuration file in your `config` directory.

## Configuration

The published configuration file contains the following settings:

- `web_interface`: Enable or disable the web interface for the updater
- `middleware`: The middleware to apply to the web routes
- `update_path`: The path where update packages will be stored
- `backup_path`: The path where backups will be stored
- `backup_retention`: The number of backups to keep (0 to keep all)
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
php artisan project:update path/to/update-package.zip
```

or to skip confirmation:

```bash
php artisan project:update path/to/update-package.zip --force
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

## Security

- All update operations require authentication and authorization
- Update packages can be verified with a digital signature
- Backups are created before any changes are applied

## License

This package is open-sourced software licensed under the MIT license.