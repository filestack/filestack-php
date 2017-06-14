<?php
use Filestack\FilestackClient;
use Filestack\FilestackSecurity;
use Filestack\Filelink;
use Filestack\FilestackException;

$test_api_key = 'YOUR_FILESTACK_API_KEY';
$test_secret = 'YOUR_FILESTACK_SECURITY_SECRET';

$security = new FilestackSecurity($test_secret);
$client = new FilestackClient($test_api_key, $security);

$file_handle = 'bzjSo5gAT76ra25sjk4c';

# get sfw flag with client
$result_json = $client->getSafeForWork($file_handle);
var_dump($result_json);

# get sfw flag with filelink
$filelink = new Filelink($file_handle,
    $test_api_key, $security);

$json_result = $filelink->getSafeForWork();
var_dump($json_result);

# example json result
/*
{
  "sfw": true
}
*/

