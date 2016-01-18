[<img src="https://travis-ci.org/tinify/magento2-plugin.svg?branch=master" alt="Build Status">](https://travis-ci.org/tinify/magento2-plugin)

# Compress JPEG & PNG images for Magento 2

Make your Magento 2 store faster by compressing your JPEG and PNG images.

This plugin automatically optimizes your images by integrating with the
popular image compression services TinyJPG and TinyPNG.

Learn more about TinyJPG and TinyPNG at https://tinypng.com/.

## Installation

This Magento 2 module can be installed with Composer
(https://getcomposer.org/download/). Once the new Magento 2 Marketplace is
lauched, it will also be featured there.

From the command line, do the following in your Magento 2 installation directory:

```
composer require tinify/magento2
php bin/magento setup:upgrade
```

## Contact us

Got questions or feedback? Let us know! Contact us at support@tinypng.com.

## Information for plugin contributors

### Prerequisites

* PHP 5.5 or newer.
* MySQL 5.6 or newer (integration tests).
* Composer (https://getcomposer.org/download/).

### Running the unit tests

```
composer install
vendor/bin/phpunit
```

### Installing development version of plugin

Make sure to set `"minimum-stability": "dev"` in `composer.json` before you start.

```
composer config repositories.tinify vcs https://github.com/tinify/magento2-plugin
composer require tinify/magento2:dev-master
bin/magento setup:upgrade
```

## License

This software is licensed under the MIT License. [View the license](LICENSE).
