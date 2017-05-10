<?php
use Filestack\FilestackClient;
use Filestack\Filelink;
use Filestack\FilestackException;

$test_api_key = 'AefuF1HdTzGBlwfxk1FYWz';
$test_filepath = __DIR__ . '/../tests/testfiles/calvinandhobbes.jpg';

# Filestack client examples
$client = new FilestackClient($test_api_key);

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
$metadata = $client->getMetaData($filelink->handle, $fields);
# or
$metadata = $client->getMetaData($filelink->url(), $fields);
var_dump($metadata);

// get content of a file
$content = $client->getContent($filelink->handle);
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

// transform an image from url
$url = "https://cdn.filestackcontent.com/vA9vFnjRVGmEbNPy3beQ";
$transform_tasks = [
    'crop'      => ['dim' => '[10,20,200,250]'],
    'resize'    => ['w' => '100', 'h' => '100'],
    'rotate'    => ['b' => '00FF00', 'd' => '45'],
];

$content = $client->transform($url, $transform_tasks);

// save file to local drive
$filepath = __DIR__ . '/../tests/testfiles/transformed_file.jpg';
file_put_contents($filepath, $content);

// transform an image from url with store()
$url = "https://cdn.filestackcontent.com/vA9vFnjRVGmEbNPy3beQ";
$transform_tasks = [
    'resize'    => ['width' => '100', 'height' => '100'],
    'rotate'    => ['background' => 'red', 'deg' => '45'],
    'store'     => []
];

$result = $client->transform($url, $transform_tasks);
$json = json_decode($result);

var_dump($json);

/* overwrite() and delete() require security settings turned on */

// zipping files
$sources = [
    'https://d1wtqaffaaj63z.cloudfront.net/images/20150617_143146.jpg',
    $filelink->handle
];

$contents = $client->zip($sources);
$filepath = __DIR__ . '/../tests/testfiles/contents-zipped.zip';
file_put_contents($filepath, $contents);

