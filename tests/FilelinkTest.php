<?php
use Filestack\Filelink;
use Filestack\FilestackException;

class FilelinkTest extends \PHPUnit_Framework_TestCase
{
    const TEST_API_KEY = 'A5lEN6zU8SemSBWiwcGJhz';

    protected $test_file_url;
    protected $test_file_handle;

    protected function setUp()
    {
        $this->test_file_url = 'https://cdn.filestackcontent.com/6mpd6Vs6TOOQ1Xny1owS';
        $this->test_file_handle = '6mpd6Vs6TOOQ1Xny1owS';
    }

    public function tearDown()
    {
        // teardown calls
    }

    /*
     * Test initializing Filelink intialized with handle and API Key
     */
    public function testFilelinkInitialized()
    {
        $filelink = new Filelink($this->test_file_handle, self::TEST_API_KEY);
        $this->assertEquals($filelink->handle, $this->test_file_handle);
        $this->assertEquals($filelink->api_key, self::TEST_API_KEY);
    }

    /*
     * Test storing a filelink
     */
    public function testFilelinkStore()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{url: "https://cdn.filestack.com/somefilehandle"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $filelink = new Filelink($this->test_file_handle, self::TEST_API_KEY, $stub_http_client);
        $new_filelink = $filelink->store();
        $this->assertNotNull($new_filelink);
    }
}