# Filesystem Gallery
Laravel 4.2 library that scans and indexes a filesystem folder and uses it to build a structure of "albums", "sub-albums" and media files.

## Installation
1. Add the following to your composer.json file:
```
{
    ...
    "repositories": [{
        ...
        "type": "git",
        "url": "https://github.com/brokunMusheen/fs-gallery.git"
    }],
    ...
    "require": {
        ...
        "brokunMusheen/f-sgallery": "1.2.*"
    }
}
```
2. Run `composer update`
3. Add `'BrokunMusheen\FSGallery\FSGalleryServiceProvider',` to the `providers` array in app/config/app.php
4. Add `'FSGallery' => 'BrokunMusheen\FSGallery\Gallery',` to the `aliases` array in app/config/app.php
5. Run `php artisan config:publish brokunMusheen/f-sgallery` to copy package config to app/config/package/brokunMusheen/f-sgallery/config.php
