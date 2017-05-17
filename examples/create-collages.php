<?php
use Filestack\FilestackClient;
use Filestack\FilestackSecurity;
use Filestack\Filelink;
use Filestack\FilestackException;

$test_api_key = 'YOUR_FILESTACK_API_KEY';
$test_secret = 'YOUR_FILESTACK_SECURITY_SECRET';
$test_filepath = __DIR__ . '/../tests/testfiles/calvinandhobbes.jpg';

$sources = [
    '9K1BZLt6SAyztVaOtAQ4',
    'FWOrzDcpREanJDI3hdR5',
    'Vi6RUEi6TgCSo9FXYVxP',
    'https://d1wtqaffaaj63z.cloudfront.net/images/E-0510.JPG'
];

/**
 * You can create a collage using the client
 */

$security = new FilestackSecurity($test_secret);
$client = new FilestackClient($test_api_key, $security);

$collage_filelink = $client->collage($sources, 800, 600);
$destination = __DIR__ . '/../tests/testfiles/collage-test.png';

$result = $collage_filelink->download($destination);
# or
$contents = $collage_filelink->getContent();
file_put_contents($destination, $contents);

/**
 * Or you can create a collage using a filelink
 */

$filelink = $client->upload($test_filepath);
$collage_filelink = $filelink->collage($sources, 800, 600)->save();

$result = $collage_filelink->download($destination);
# or
$contents = $collage_filelink->getContent();
file_put_contents($destination, $contents);

// delete remote file
$filelink->delete();
