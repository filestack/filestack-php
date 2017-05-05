<?php
use Filestack\FilestackClient;
use Filestack\Filelink;
use Filestack\FilestackException;

$test_api_key = 'AefuF1HdTzGBlwfxk1FYWz';
$test_filepath = __DIR__ . '/../tests/testfiles/calvinandhobbes.jpg';

# Filestack client examples
$client = new FilestackClient($test_api_key);

// upload a file
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
$metadata = $client->getMetaData($filelink->handle, $fields);
# or
$metadata = $client->getMetaData($filelink->url(), $fields);
var_dump($metadata);

// get content of a file
$content = $client->getContent($filelink->handle;
# or
$content = $client->getContent($filelink->url());

// save file to local drive
$filepath = __DIR__ . '/../tests/testfiles/' . $metadata['filename'];
file_put_contents($filepath, $content);

// download a file
$destination = __DIR__ . '/../tests/testfiles/my-custom-filename.jpg';
$result = $client->download($filelink->handle, $destination);
# or
$result = $client->download($filelink->url(), $destination);
var_dump($result);

// overwrite() require security settings turned on for this operation
// delete() require security settings turned on for this operation
