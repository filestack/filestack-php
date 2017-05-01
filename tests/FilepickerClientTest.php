<?php

use Filestack\FilepickerClient;

const TEST_API_KEY = 'A5lEN6zU8SemSBWiwcGJhz';

class FilepickerClientTest extends PHPUnit_Framework_TestCase {

    public function testFilepickerInitialized()
    {
        $client = new FilepickerClient(TEST_API_KEY);
        $this->assertEquals($client->api_key, TEST_API_KEY);
    }

    public function testStoreSuccess()
    {
        $filepath = __DIR__ . "/testfile.txt";

        $client = new FilepickerClient(TEST_API_KEY);
        $response = $client->store($filepath);
        $this->assertEquals($response->getStatusCode(), 200);
    }
}