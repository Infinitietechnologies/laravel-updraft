# Laravel Updraft

A Laravel package to easily manage application updates through versioned update packages. Laravel Updraft provides a secure, reliable way to update your Laravel applications with minimal downtime.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/infinitietechnologies/laravel-updraft.svg)](https://packagist.org/packages/infinitietechnologies/laravel-updraft)
[![License](https://img.shields.io/github/license/infinitietechnologies/laravel-updraft)](https://github.com/infinitietechnologies/laravel-updraft/blob/master/LICENSE)
[![Laravel Version](https://img.shields.io/badge/Laravel-10.x%20%7C%2011.x%20%7C%2012.x-red)](https://laravel.com/)

## Table of Contents

- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
  - [Creating Update Packages](#creating-update-packages)
  - [Applying Updates](#applying-updates)
- [Update Process](#update-process)
- [Customizing the UI](#customizing-the-ui)
- [Features](#features)
- [Security](#security)
  - [Authentication and Authorization](#authentication-and-authorization)
- [Roadmap](#roadmap)
- [Contributing](#contributing)
- [Credits](#credits)
- [License](#license)

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

To publish the language files for internationalization:

```bash
php artisan vendor:publish --tag=laravel-updraft-translations
```

Finally, publish the assets to make them available publicly:

```bash
php artisan vendor:publish --tag=laravel-updraft-assets
```

Run the migrations to create the necessary database tables:

```bash
php artisan migrate
```

## Configuration

The published configuration file contains the following settings:

- `web_interface`: Enable or disable the web interface for the updater
- `middleware`: The middleware to apply to the web routes
- `update_path`: The path where update packages will be stored
- `backup_path`: The path where backups will be stored
- `backup_retention`: The number of backups to keep (0 to keep all)
- `layout`: The blade layout to use for the updraft views (defaults to 'layouts.app')
- `content`: The content section name in your layout (defaults to 'content')

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

The web interface provides a user-friendly way to upload and apply updates, with detailed error messages if something goes wrong.

#### Via Artisan Command

```bash
php artisan updraft:update path/to/update-package.zip
```

or to skip confirmation:

```bash
php artisan updraft:update path/to/update-package.zip --force
```

The command-line interface is useful for automated deployments or when you prefer working in the terminal.

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
9. Cleans up temporary files

Detailed error information is provided both in the logs and to the user if any step fails.

## Customizing the UI

Laravel Updraft views use a layout file that can be configured in the `laravel-updraft.php` config file:

```php
'layout' => 'layouts.app',
'content' => 'content',
```

Change these to use your own layout file and content section. For further customization, publish the views:

```bash
php artisan vendor:publish --tag=laravel-updraft-views
```

The views use Bootstrap 5 and Font Awesome for styling and are fully responsive.

## Features

- **Simple Update Process**: Streamlined workflow for applying updates to your Laravel application
- **Version Control**: Ensures that updates are only applied to compatible application versions
- **Automatic Backups**: Creates backups before applying updates for safety
- **Web Interface**: User-friendly interface for uploading and applying updates
- **Command Line Support**: Apply updates via Artisan commands for automated deployment
- **Flexible Manifest System**: Detailed manifests for controlling update behavior
- **Customizable UI**: Configure the layout and publish views to match your application's design
- **Security First**: Authentication and authorization built-in
- **Enhanced Error Handling**: Detailed error reporting to help diagnose issues
- **Internationalization Support**: Built-in support for multiple languages
- **Progress Tracking**: Visual progress indicators during the update process
- **Automatic File Management**: Handles adding, modifying, and removing files as needed
- **Database Migration Support**: Automatically runs migrations included in update packages
- **Configuration Updates**: Updates configuration files with new settings
- **Post-Update Commands**: Executes specified commands after applying updates
- **Update History**: Keeps a detailed history of all applied updates

## Security

### Authentication and Authorization

- All update operations require authentication and authorization
- The web interface is protected by the middleware specified in the config file
- By default, only users with the 'manage-updates' permission can access the web interface
- Error logs include detailed information about failures for security auditing
- File integrity checks ensure updates haven't been tampered with

## Roadmap

Here's our plan for future improvements to Laravel Updraft:

### Short-term (Next Release)
- **Improved File Diffing**: Show file differences before applying updates
- **Update Channels**: Support for different update channels (stable, beta, nightly)
- **Update Notifications**: Email notifications for successful/failed updates
- **Enhanced Package Validation**: More robust validation of update packages
- **Signature Verification**: Cryptographic validation of update package signatures for security

### Mid-term
- **Update Package Builder Tool**: GUI for creating update packages
- **Remote Update Repository**: Support for fetching updates from a remote repository
- **Update Dependencies**: Allow updates to specify dependencies
- **Staged Updates**: Apply updates in stages with validation between steps
- **Update Testing Mode**: Test updates in a sandbox environment before applying

### Long-term
- **Real-time Progress Updates**: WebSocket support for real-time update progress
- **Multi-server Deployment**: Coordinate updates across multiple servers
- **Plugin System**: Allow extensions to customize the update process
- **Update Scheduling**: Schedule updates for off-peak hours
- **Automated Testing**: Test deployments automatically after updates
- **Health Checks**: Pre and post-update system health checks
- **Advanced Metrics**: Gather and analyze update performance and reliability metrics

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please make sure your code follows our coding standards and includes tests for new features.

## Credits

- [Infinitie Technologies](https://github.com/Infinitietechnologies)
- [All Contributors](../../contributors)

## License

This package is open-sourced software licensed under the MIT license. See the [LICENSE](LICENSE) file for more information.