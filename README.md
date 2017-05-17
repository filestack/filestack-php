# Filestack PHP SDK
<a href="https://www.filestack.com"><img src="https://filestack.com/themes/filestack/assets/images/press-articles/color.svg" align="left" hspace="10" vspace="6"></a>
This is the official PHP SDK for Filestack - API and content management system that makes it easy to add powerful file uploading and transformation capabilities to any web or mobile application.

## Resources

* [Filestack](https://www.filestack.com)
* [Documentation](https://www.filestack.com/docs)

## Installing

Install ``filestack`` with composer, either run

    $ php composer.phar require --prefer-dist filestack/filestack-php "*"

or add

```
"filestack/filestack-php": "*"
```

or download from GitHub

    https://github.com/filestack/filestack-php.git

## Usage

Filestack library gives you access to three useful classes:

* `FilestackClient` - for easy file upload (creates Filelink objects)
* `Filelink` - for file handling (downloading, converting etc.)
* `FileSecurity` - for applying policy and signature values to your API calls

### Uploading files
First, you need to create an instance of FilestackClient

```php
use Filestack\FilestackClient;

$client = new FilestackClient('YOUR_API_KEY');
```

### Storage
Amazon S3 is used to store your files by default. If you wish to use a different one, you can pass in additional parameter 'location' when making upload() and store calls

```php
$client = FilestackClient('YOUR_API_KEY');
$extras = [
    'Location' => 'dropbox',
    'Filename' => 'somefilename.jpg',
];

$filepath = '/path/to/file';
$filelink = $client->upload($filepath);
```

### Manipulating files

Filelink objects can be created in two ways:

 - by uploading a file with using FilestackClient
 - by initializing Filelink with file handle and api_key

First method was shown above, the second method is also very easy and will create objects representing files that were already uploaded.

```php
use Filestack\filelink;

$file = new Filelink('pGj2wWfBTMuXhWe2J3bL', 'YOUR_API_KEY');
$transformed_filelink = $filelink
            ->circle()
            ->blur(['amount' => '20'])
            ->save();
```

For more examples, see the [examples/](examples/) folder in this project.

## Versioning

Filestack PHP SDK follows the [Semantic Versioning](http://semver.org/).

## Code Standard

- PSR-2 coding standard (http://www.php-fig.org/psr/psr-2/)
- PSR-4 autoloading standard (http://www.php-fig.org/psr/psr-4/)
- phpDoc documentation comments standard (https://www.phpdoc.org/docs/latest/getting-started/your-first-set-of-documentation.html)

## Testing

- To run tests, from the project root director, run
```
vendor/bin/phpunit
```

- To generate coverage report, run following command (will generage html files under
directory tests/coverage)
```
vendor/bin/phpunit --coverage-html=tests/coverage
```

- To run lint and code-standard fixer, run bash file ./runfixer.sh or individually:
```
php php-cs-fixer-v2.phar fix filestack/
php php-cs-fixer-v2.phar fix tests/
php php-cs-fixer-v2.phar fix examples/
```

## Issues

If you have problems, please create a [Github Issue](https://github.com/filestack/filestack-php/issues).

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Credits

Thank you to all the [contributors](https://github.com/filestack/filestack-php/graphs/contributors).

## Other Resources

- [travis_ci](http://travis-ci.org/filestack/filestack-php)
- [travis_ci_badge](https://travis-ci.org/filestack/filestack-php.svg?branch=master)

- [code_climate](https://codeclimate.com/github/filestack/filestack-php)
- [code_climate_badge](https://filestack.com/themes/filestack/assets/images/press-articles/color.svg)

- [coveralls](https://coveralls.io/github/filestack/filestack-php?branch=master)
- [![Coverage Status](https://coveralls.io/repos/github/filestack/filestack-php/badge.svg?branch=master)](https://coveralls.io/github/filestack/filestack-php?branch=master)
