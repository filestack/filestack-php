<?php
use Filestack\FilepickerClient;

# Filestack convert task example

$client = new FilepickerClient('YOUR_API_KEY');

$client->convert_external("http://some.image/aaa.jpg");  # return Transformation object

$flink = new Filelink("some-handle", "apikey");

# apikey optional, can be also set like this
$flink->api_key ='YOUR_API_KEY';

# conversions
$result = $flink->resize(w=100)->crop(d=[0, 0, 100, 200]);

$sec = new Security('xyz');

$filelink = $result->store();  # returns Filelink object
$result->download('aaa_sx123', $sec);  # save to local drive
$result->get_content('aaa_sx123', $sec);
