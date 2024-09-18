<p align="center"><img src="logo.svg" align="center" width="100"/></p>
<h1 align="center">Filestack PHP</h1>
<p align="center">
  <a href="http://travis-ci.org/filestack/filestack-php">
    <img src="https://img.shields.io/travis/filestack/filestack-php.svg">
  </a>
  <a href="https://coveralls.io/github/filestack/filestack-php?branch=master">
    <img src="https://coveralls.io/repos/github/filestack/filestack-php/badge.svg?branch=master">
  </a>
   <a href="https://codeclimate.com/github/filestack/filestack-php">
    <img src="https://codeclimate.com/github/filestack/filestack-php/badges/gpa.svg">
  </a>
</p>
This is the official PHP SDK for Filestack - API and content management system that makes it easy to add powerful file uploading and transformation capabilities to any web or mobile application.

## Requirements

* PHP 8.3+

## Resources

* [Filestack](https://www.filestack.com)
* [Documentation](https://www.filestack.com/docs)

## Installing

Install ``filestack`` with composer, either run

    $ composer require --prefer-dist filestack/filestack-php

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

Call the upload() function

```php

$filelink = $client->upload('/path/to/file');

```

### Storage

Amazon S3 is used to store your files by default. If you wish to use a different one, you can pass in additional parameter 'location' when making upload() and store calls

```php
$client = new FilestackClient('YOUR_API_KEY');
$extras = [
    'Location' => 'dropbox',
    'Filename' => 'somefilename.jpg',
];

$filepath = '/path/to/file';
$filelink = $client->upload($filepath);

# get metadata of file
$metadata = $client->getMetaData($filelink->handle, $fields);

# get content of a file
$content = $client->getContent($filelink->handle);

# download a file
$destination = '/path/to/file';
$result = $client->download($filelink->handle, $destination);

# overwrite a file
$filelink2 = $client->overwrite('/path/to/file', $filelink->handle);
```

### Manipulating files

Filelink objects can be created in two ways:

 - by uploading a file using FilestackClient
 - by initializing Filelink with file handle and api_key

First method was shown above, the second method is also very easy and will create objects representing files that were already uploaded.

```php
use Filestack\filelink;

$filelink = new Filelink('some-file-handle', 'YOUR_API_KEY');

# transforming an image
$transformed_filelink = $filelink
            ->circle()
            ->blur(['amount' => '20'])
            ->save();

# get metadata
$metadata = $filelink->getMetaData();

# get content of a file
$content = $filelink->getContent();

$filepath = '/path/to/file';

# download a file
$filelink->download($filepath);

# overwrite remote file with local file
$filelink->overwrite($filepath);

# delete remote file
$filelink->delete();

```

### Tagging files and detecting safe for work content

```php
use Filestack\FilestackClient;
use Filestack\FilestackSecurity;

$security = new FilestackSecurity('YOUR_SECURITY_SECRET');
$client = new FilestackClient('YOUR_API_KEY', $security);

$file_handle = 'some-file-handle';

# get tags with client
$result_json = $client->getTags($file_handle);

# get tags with filelink
$filelink = new Filelink($file_handle, 'YOUR_API_KEY', $security);

$json_result = $filelink->getTags();

# get safe for work flag with client
$result_json = $client->getSafeForWork($file_handle);

# get safe for work flag with filelink
$json_result = $filelink->getSafeForWork();

```

For more examples, see the [examples/](examples/) folder in this project.

## Intelligent Ingestion

The Intelligent Ingestion feature allows user to upload a file in chunks of
not precised size. This creates a more stable upload flow that ensures the
file being uploaded will eventually complete successfully, regardless of
network latency or timeout errors.

However, the upload process may be slower than the normal upload flow for
large files, as there are errors are retried using the exponential backoff
retry strategy.

Lastly, this feature has to be turned on for the apikey being used.  To turn
on this feature please contact Filestack at support@filestack.com.

```
$client = new FilestackClient('YOUR_API_KEY');
$filelink = $client->upload('/path/to/file', ['intelligent' => true]);
```

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
directory coverage/)
```
vendor/bin/phpunit --coverage-xml=coverage
```

- To run PHPMD for CodeClimate checks
```
vendor/bin/phpmd filestack xml phpmd-rules.xml > logs/phpmd-report-filestack.xml
vendor/bin/phpmd tests xml phpmd-rules.xml > logs/phpmd-report-tests.xml
```

## Generating documentation

To get project metrics use phar file for https://github.com/sebastianbergmann/phploc

```
./phploc.phar --log-xml=phploc.xml .
```

To generate documentation use phar file from https://github.com/theseer/phpdox

```
./phpdox.phar
```

## Issues

If you have problems, please create a [Github Issue](https://github.com/filestack/filestack-php/issues).

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Credits

Thank you to all the [contributors](https://github.com/filestack/filestack-php/graphs/contributors).
