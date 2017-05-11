<?php
use Filestack\FilestackClient;
use Filestack\FilestackSecurity;
use Filestack\Filelink;
use Filestack\FilestackException;

$test_api_key = 'A5lEN6zU8SemSBWiwcGJhz';
$test_secret = '3UAQ64UWMNCCRF36CY2NSRSPSU';
$test_filepath = __DIR__ . '/../tests/testfiles/calvinandhobbes.jpg';

# Filestack client examples
$security = new FilestackSecurity($test_secret);
$client = new FilestackClient($test_api_key, $security);

// upload a file
$filelink = null;
$options = ['Filename' => 'somefilename.jpg'];

try {
    $filelink = $client->upload($test_filepath, $options);
    var_dump($filelink);
} catch (FilestackException $e) {
    echo $e->getMessage();
    echo $e->getCode();
}

// get metadata of file
$fields = [];
$metadata = $client->getMetaData($filelink->url(), $fields);
var_dump($metadata);

// get content of a file
$content = $client->getContent($filelink->url());

// save file to local drive
$filepath = __DIR__ . '/../tests/testfiles/' . $metadata['filename'];
file_put_contents($filepath, $content);

// download a file
$destination = __DIR__ . '/../tests/testfiles/my-custom-filename.jpg';
$result = $client->download($filelink->url(), $destination);
var_dump($result);

// overwrite a file
$filelink2 = $client->overwrite($test_filepath, $filelink->handle);
var_dump($filelink2);
