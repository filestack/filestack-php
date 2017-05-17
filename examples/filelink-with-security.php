<?php
use Filestack\FilestackClient;
use Filestack\FilestackSecurity;
use Filestack\Filelink;
use Filestack\FilestackException;

$test_api_key = 'YOUR_FILESTACK_API_KEY';
$test_secret = 'YOUR_FILESTACK_SECURITY_SECRET';
$test_filepath = __DIR__ . '/../tests/testfiles/calvinandhobbes.jpg';

// upload a file to test
$security = new FilestackSecurity($test_secret);
$client = new FilestackClient($test_api_key, $security);
$uploaded_filelink = $client->upload($test_filepath);

# Filestack client examples
$file_handle = $uploaded_filelink->handle;
$filelink = new Filelink($file_handle,
    $test_api_key, $security);

// get metadata
$metadata = $filelink->getMetaData();

// get content of a file
$content = $filelink->getContent();

// save file to local drive
$filepath = __DIR__ . '/../tests/testfiles/' . $metadata['filename'];
file_put_contents($filepath, $content);

// download a file
$filelink->download($test_filepath);

// overwrite remote file with local file
$filelink->overwrite($test_filepath);

// delete remote file
$filelink->delete();
