<?php
use Filestack\Filelink;
use Filestack\FilestackException;

$test_api_key = 'YOUR_FILESTACK_API_KEY';
$test_secret = 'YOUR_FILESTACK_SECURITY_SECRET';
$test_filepath = __DIR__ . '/../tests/testfiles/testing-download.jpg';

# Filestack client examples
$filelink = new Filelink('a-filestack-handle', $test_api_key);

// get metadata
$metadata = $filelink->getMetaData();

// get content of a file
$content = $filelink->getContent();

// save file to local drive
$filepath = __DIR__ . '/../tests/testfiles/' . $metadata['filename'];
file_put_contents($filepath, $content);

// download a file
$filelink->download($test_filepath);

// delete() and overwrite() methods require security
