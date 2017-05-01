<?php
namespace Filestack\Tests;

use Filestack\FilepickerClient;
use Filestack\FilestackException;
use Filestack\Tests\MockHttpResponse;

class FilepickerClientTest extends \PHPUnit_Framework_TestCase
{
    const TEST_API_KEY = 'A5lEN6zU8SemSBWiwcGJhz';

    protected $http_client;
    protected $test_filepath;

    protected function setUp()
    {
        $this->http_client = new \GuzzleHttp\Client();
        $this->test_filepath = __DIR__ . "/testfile.txt";
    }

    public function tearDown()
    {
        // teardown calls
    }

    /*
     * Test initializing FilepickerClient with an API Key
     */
    public function testFilepickerInitialized()
    {
        $client = new FilepickerClient(self::TEST_API_KEY, $this->http_client);
        $this->assertEquals($client->api_key, self::TEST_API_KEY);
    }

    /*
     * Test calling the store function with an invalid api key.
     */
    public function testStoreInvalidKey()
    {
        $mock_response = new MockHttpResponse(403);
        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
            ->willReturn($mock_response);

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(403);

        $client = new FilepickerClient("some_bad_key", $stub_http_client);
        $filelink = $client->store($this->test_filepath);
    }

    /*
     * Test calling the store function with a valid api key.
     */
    public function testStoreSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{url: "https://cdn.filestack.com/somefilehandle"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);


        $client = new FilepickerClient(self::TEST_API_KEY, $stub_http_client);
        $filelink = $client->store($this->test_filepath);
        $this->assertNotNull($filelink);
    }
}
