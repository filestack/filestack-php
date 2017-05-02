<?php
use Filestack\FilepickerClient;
use Filestack\FilestackException;

class FilepickerClientTest extends \PHPUnit_Framework_TestCase
{
    const TEST_API_KEY = 'A5lEN6zU8SemSBWiwcGJhz';

    protected $test_filepath;
    protected $test_file_url;

    protected function setUp()
    {
        $this->test_filepath = __DIR__ . '/calvinandhobbes.jpg';
        $this->test_file_url = 'https://cdn.filestackcontent.com/6mpd6Vs6TOOQ1Xny1owS';
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
        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $client = new FilepickerClient(self::TEST_API_KEY, $stub_http_client);
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

        $client = new FilepickerClient('some_bad_key', $stub_http_client);
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

    /*
     * Test calling the store function with a valid api key and options
     */
    public function testStoreSuccessWithOptions()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{url: "https://cdn.filestack.com/somefilehandle"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilepickerClient(self::TEST_API_KEY, $stub_http_client);

        $security = null;
        $extras = [
            'Location' => 'dropbox',
            'Filename' => 'somefilename.jpg',
        ];

        $filelink = $client->store($this->test_filepath);
        $this->assertNotNull($filelink);
    }

    /*
     * Test calling the store function with a valid api key and URL of file
     */
    public function testStoreUrlSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{url: "https://cdn.filestack.com/somefilehandle"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilepickerClient(self::TEST_API_KEY, $stub_http_client);
        $filelink = $client->store($this->test_file_url);
        $this->assertNotNull($filelink);
    }
}
