<?php
use Filestack\FilestackClient;
use Filestack\FilestackSecurity;
use Filestack\Filelink;
use Filestack\FilestackException;

$test_api_key = 'YOUR_FILESTACK_API_KEY';
$test_secret = 'YOUR_FILESTACK_SECURITY_SECRET';
$source = 'https://www.wikipedia.org/portal/wikipedia.org/' .
    'assets/img/Wikipedia-logo-v2@1.5x.png';

/**
 * You can create convert a filelink or url from one format to another,
 * to see the list of formats, go to :
 * https://www.filestack.com/docs/image-transformations/conversion
 */

$security = new FilestackSecurity($test_secret);
$client = new FilestackClient($test_api_key, $security);

$output_options = [
    'background' => 'white',
    'density' => 50,
    'compress' => true,
    'colorspace' => 'input',
    'quality' => 80,
    'strip' => true,
    'pageformat' => 'letter',
    'pageorientation' => 'landscape'
];

$filelink = $client->convertFile($source, 'pdf', $output_options);
$destination = __DIR__ . '/../tests/testfiles/convert-file-test.pdf';

$result = $filelink->download($destination);
# or
$contents = $filelink->getContent();
file_put_contents($destination, $contents);

/**
 * Or you can convert using a filelink object
 */

$filelink = $client->upload($test_filepath);
$converted_filelink = $filelink->convertFile($source, 'pdf', $output_options);

$destination = __DIR__ . '/../tests/testfiles/convert-filelink-test.pdf';
$result = $converted_filelink->download($destination);
# or
$contents = $converted_filelink->getContent();
file_put_contents($destination, $contents);

// delete remote file
$filelink->delete();
