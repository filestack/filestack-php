[![Travis CI][travis_ci_badge]][travis_ci]
[![Coveralls][coveralls_badge]][coveralls]
[![Code Climate][code_climate_badge]][code_climate]

# Filestack Python SDK
<a href="https://www.filestack.com"><img src="https://filestack.com/themes/filestack/assets/images/press-articles/color.svg" align="left" hspace="10" vspace="6"></a>
This is the official PHP SDK for Filestack - API and content management system that makes it easy to add powerful file uploading and transformation capabilities to any web or mobile application.

## Resources

* [Filestack](https://www.filestack.com)
* [Documentation](https://www.filestack.com/docs)
* [API Reference](https://filestack.github.io/)

## Installing

Install ``filestack`` with composer, either run

    $ php composer.phar require --prefer-dist bryglen/yii2-twillio "*"

or add

```
"filestack/filestack-python": "*"
```

or download from GitHub

    https://github.com/filestack/filestack-php.git

## Usage

Filestack library gives you access to two useful classes:

* `FilepickerClient` - for easy file upload (creates FilepickerFile objects)
* `FilepickerFile` - for file handling (downloading, converting etc.)

### Uploading files
First, you need to create an instance of FilepickerClient

```php
use Filestack\FilepickerClient;

$client = new FilepickerClient('YOUR_API_KEY');

# or
$client = new FilepickerClient();
$client.set_api_key('YOUR_API_KEY');
```

### Storage
Amazon S3 is used to store your files by default. If you wish to use a different one, you can initialize FilepickerClient with an additional `storage` argument or use `set_storage()` method:

```php
$client = FilepickerClient('YOUR_API_KEY', 'azure');
# or
$client = FilepickerClient('YOUR_API_KEY');
$client.set_storage('dropbox');
```
### Manipulating files

FilepickerFile objects can be created in three ways:

 - by uploading a file with using FilepickerClient
 - by initializing FilepickerFile with file handle
 - by initializing FilepickerFile with a Filepicker url

First method was shown above, the two other are also very easy and will create objects representing files that were already uploaded.

```php
use Filestack\FilepickerClient;
$file = new FilepickerFile('pGj2wWfBTMuXhWe2J3bL');
# or
$file = new FilepickerFile('https://www.filepicker.io/api/file/pGj2wWfBTMuXhWe2J3bL');
```

## Versioning

Filestack Python SDK follows the [Semantic Versioning](http://semver.org/).

## Issues

If you have problems, please create a [Github Issue](https://github.com/filestack/filestack-php/issues).

## Contributing

Please see [CONTRIBUTING.md](https://github.com/filestack/filestack-php/blob/master/CONTRIBUTING.md) for details.

## Credits

Thank you to all the [contributors](https://github.com/filestack/filestack-php/graphs/contributors).


## Installing

[travis_ci]: http://travis-ci.org/filestack/filestack-php
[travis_ci_badge]: https://travis-ci.org/filestack/filestack-php.svg?branch=master
[code_climate]: https://codeclimate.com/github/filestack/filestack-php
[code_climate_badge]: https://codeclimate.com/github/filestack/filestack-php.png
[coveralls]: https://coveralls.io/github/filestack/filestack-php?branch=master
[coveralls_badge]: https://coveralls.io/repos/github/filestack/filestack-php/badge.svg?branch=master
