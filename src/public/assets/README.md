# Laravel Updraft Assets

This directory contains all the front-end assets used by the Laravel Updraft package.

## Directory Structure

- `css/` - Contains CSS files
  - `bootstrap.min.css` - Bootstrap 5.3 CSS
  - `fontawesome.min.css` - Font Awesome 6.1.1

- `js/` - Contains JavaScript files
  - `bootstrap.bundle.min.js` - Bootstrap 5.3 JavaScript with Popper.js
  - `jquery.min.js` - jQuery 3.6.0

- `fonts/` - Contains font files for Font Awesome

- `plugins/` - Contains third-party libraries
  - `filepond/` - FilePond file upload library
    - `filepond.min.css` - FilePond core CSS
    - `filepond.min.js` - FilePond core JavaScript
    - `plugins/` - FilePond plugins
      - `file-validate-type/` - Type validation plugin
      - `file-validate-size/` - Size validation plugin
      - `file-poster/` - File poster plugin
      - `image-preview/` - Image preview plugin

## Usage

These assets are automatically published to your application's public directory when you publish the Laravel Updraft assets:

```bash
php artisan vendor:publish --tag=laravel-updraft-assets
```

All assets are referenced using the `asset()` helper in the views, pointing to the `vendor/laravel-updraft/assets` path.