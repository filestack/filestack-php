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
