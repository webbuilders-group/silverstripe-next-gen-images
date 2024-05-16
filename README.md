Next Gen Images for Silverstripe
=================
![CI](https://github.com/webbuilders-group/silverstripe-next-gen-images/actions/workflows/ci.yml/badge.svg)

Adds Support Automatic Generation of WebP Images in Silverstripe

## Maintainer Contact
* Ed Chipman ([UndefinedOffset](https://github.com/UndefinedOffset))

## Requirements
* [Silverstripe Assets](https://github.com/silverstripe/silverstripe-assets) 2.2+


## Installation
```
composer require webbuilders-group/silverstripe-next-gen-images
```


## Usage
By default this module replaces the shortcode used when you insert an image in the WYSIWYG and when you use an image in your templates with markup that automatically generates a `<picture>` tag with a WebP file as one of the sources (falling back to the originally uploaded image). This module also enables uploading of WebP images and use in the WYSIWYG in supporting browsers but they do not use the `<picture>` tag when you use a WebP image.

You can also call `getWebP` on the `SilverStripe\Assets\Image` class or `WebP` in your templates for example `$MyImage.WebP` or even `$MyImage.ScaleWidth(100).WebP`. After you have the WebP you can continue to call other operations on the WebP just like you would any other image, for example `$MyImage.WebP.URL`.
