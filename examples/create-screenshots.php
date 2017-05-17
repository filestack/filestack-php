<?php
use Filestack\FilestackClient;
use Filestack\FilestackSecurity;
use Filestack\Filelink;
use Filestack\FilestackException;

$test_api_key = 'YOUR_FILESTACK_API_KEY';
$test_secret = 'YOUR_FILESTACK_SECURITY_SECRET';

$security = new FilestackSecurity($test_secret);
$client = new FilestackClient($test_api_key, $security);

// take a screenshot of a url
$url = 'https://en.wikipedia.org/wiki/Main_Page';
$screenshot_filelink = $client->screenshot($url);
$destination = __DIR__ . '/../tests/testfiles/screenshot-test.png';

$result = $screenshot_filelink->download($destination);
# or get contents then save
$contents = $screenshot_filelink->getContent();
file_put_contents($destination, $contents);

// delete remote file
$screenshot_filelink->delete();
