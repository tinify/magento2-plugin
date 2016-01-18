[<img src="https://travis-ci.org/tinify/magento2-plugin.svg?branch=master" alt="Build Status">](https://travis-ci.org/tinify/magento2-plugin)

# Compress JPEG & PNG images for Magento 2

Make your Magento 2 store faster by compressing your JPEG and PNG images.

This plugin automatically optimizes your images by integrating with the
popular image compression services TinyJPG and TinyPNG.

Learn more about TinyJPG and TinyPNG at https://tinypng.com/.

Do you use Magento 1? Install our Magento 1 plugin instead:
https://www.magentocommerce.com/magento-connect/compress-jpeg-png-images.html

## How does it work?

When you view a product in your webshop, Magento creates different image sizes
in its cache folders. This extension will compress these images for you
automatically. Any image sizes that are exact duplicates of each other will
only be compressed once.

Your product images are uploaded to the TinyJPG or TinyPNG service and analyzed
to apply the best possible compression. Based on the content of the image an
optimal strategy is chosen. The result is sent back to your Magento webshop and
saved in your public media folder.

On average JPEG images are compressed by 40-60% and PNG images by 50-80%
without visible loss in quality. Your webshop will load faster for your
visitors, and you’ll save storage space and bandwidth!

## Getting started

Obtain your free API key from https://tinypng.com/developers. The first 500
compressions per month are completely free, no strings attached! As each
product will be shown in different sizes, between 50 and 100 products can be
uploaded to your Magento webshop and compressed for free. You can also change
which of types of image sizes should be compressed.

If you’re a heavy user, you can compress additional images for a small
additional fee per image by upgrading your account. You can keep track of the
amount of compressions in the Magento 2 configuration section.

## Installation

The Magento 2 module can be installed with Composer
(https://getcomposer.org/download/). Once the new Magento 2 Marketplace is
lauched, it will also be featured there.

From the command line, do the following in your Magento 2 installation
directory:

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
