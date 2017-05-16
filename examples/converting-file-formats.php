<?php
use Filestack\FilestackClient;
use Filestack\FilestackSecurity;
use Filestack\Filelink;
use Filestack\FilestackException;

$test_api_key = 'A5lEN6zU8SemSBWiwcGJhz';
$test_secret = '3UAQ64UWMNCCRF36CY2NSRSPSU';
$test_filepath = __DIR__ . '/../tests/testfiles/calvinandhobbes.jpg';

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
