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

# get tags with client
$result_json = $client->getTags($file_handle);
var_dump($result_json);

# get tags with filelink
$filelink = new Filelink($file_handle,
    $test_api_key, $security);

$json_result = $filelink->getTags();
var_dump($json_result);

# example json result
/*
{
  "tags": {
    "auto": {
      "accipitriformes": 58,
      "beak": 90,
      "bird": 97,
      "bird of prey": 95,
      "fauna": 84,
      "great grey owl": 89,
      "hawk": 66,
      "owl": 97,
      "vertebrate": 92,
      "wildlife": 81
    },
    "user": null
  }
}
*/

