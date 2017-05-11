<?php
use Filestack\FilestackClient;
use Filestack\FilestackSecurity;
use Filestack\Filelink;
use Filestack\FilestackException;

$test_api_key = 'A5lEN6zU8SemSBWiwcGJhz';
$test_secret = '3UAQ64UWMNCCRF36CY2NSRSPSU';

// upload a file to test
$security = new FilestackSecurity($test_secret);
$client = new FilestackClient($test_api_key, $security);
$filelink = $client->upload($test_filepath);

// take a screenshot of a url
$url = 'https://en.wikipedia.org/wiki/Main_Page';
$screenshot_filelink = $client->screenshot($url);
$destination = __DIR__ . '/../tests/testfiles/screenshot-test.png';

$result = $screenshot_filelink->download($destination);
# or get contents then save
$contents = $screenshot_filelink->getContent();
file_put_contents($destination, $contents);

// delete remote file
$filelink->delete();
