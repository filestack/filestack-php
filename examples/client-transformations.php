<?php
use Filestack\FilestackClient;
use Filestack\FilestackSecurity;
use Filestack\Filelink;
use Filestack\FilestackException;

$test_api_key = 'YOUR_FILESTACK_API_KEY';
$test_secret = 'YOUR_FILESTACK_SECURITY_SECRET';
$test_filepath = __DIR__ . '/../tests/testfiles/calvinandhobbes.jpg';

# Filestack client examples
$security = new FilestackSecurity($test_secret);
$client = new FilestackClient($test_api_key, $security);

// transformations
$transformed_file = $client->transform($filelink->handle, $transform_tasks);
# or
$transformed_file = $client->transform($url, $transform_tasks);

$destination = __DIR__ . '/../tests/testfiles/my-transformed-file.png';

$contents = $transformed_file->getContent();
file_put_contents($destination, $contents);
# or
$result = $transformed_file->download($destination);

// zipping files
$sources = [
    'https://d1wtqaffaaj63z.cloudfront.net/images/20150617_143146.jpg',
    $filelink->handle
];

$zipped_filelink = $client->zip($sources);
$destination = __DIR__ . '/../tests/testfiles/contents-zipped.zip';

$result = $zipped_filelink->download($destination);
# or
$contents = $zipped_filelink->getContent();
file_put_contents($destination, $contents);

// debugging transformation calls
$transform_tasks = [
            'resize'    => ['w' => '100', 'h' => '100'],
            'detect_faces'    => []
        ];

/**
 * calling debug() will return a json item detailing
 * any errors the tasks may return
 */
$json_response = $client->debug($this->test_file_handle, $transform_tasks);
print_r($json_response);
