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
        $this->test_file_url = 'https://cdn.filestackcontent.com/IIkUk9D8TWKHldxmMVRt';
        $this->test_file_handle = 'IIkUk9D8TWKHldxmMVRt';
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
     * Test filelink get content
     */
    public function testFilelinkGetContent()
    {
        $mock_response = new MockHttpResponse(
            200,
            new MockHttpResponseBody("some content")
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $filelink = new Filelink($this->test_file_handle,
                        self::TEST_API_KEY, $stub_http_client);

        $result = $filelink->getContent();

        $this->assertNotNull($result);
    }

    /*
     * Test filelink get content throws exception for invalid file
     */
    public function testFilelinkGetContentNotFound()
    {
        $mock_response = new MockHttpResponse(
            404,
            "File not found"
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(404);

        $filelink = new Filelink($this->test_file_handle,
                        self::TEST_API_KEY, $stub_http_client);

        $result = $filelink->getContent();
    }

    /*
     * Test downloading a filelink
     */
    public function testFilelinkDownload()
    {
        $mock_response = new MockHttpResponse(
            200,
            'some file content'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $filelink = new Filelink($this->test_file_handle,
                        self::TEST_API_KEY, $stub_http_client);
        $destination = __DIR__ . "/my-custom-filenamed.jpg";

        $result = $filelink->download($destination);
        $this->assertTrue($result);
    }

    /*
     * Test downloading exception with file not found
     */
    public function testFilelinkDownloadNotFound()
    {
        $mock_response = new MockHttpResponse(
            404,
            'file not found'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(404);

        $filelink = new Filelink("some-bad-file-handle",
                        self::TEST_API_KEY, $stub_http_client);
        $destination = __DIR__ . "/testfile.jpg";

        $result = $filelink->download($destination);
    }

    /*
     * Test getting meta data of a filelink
     */
    public function testFilelinkGetMetadata()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{"filename": "somefile.jpg"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $filelink = new Filelink($this->test_file_handle,
                        self::TEST_API_KEY, $stub_http_client);

        $result = $filelink->getMetaData();
        $this->assertNotNull($result['filename']);
    }

    /*
     * Test getMetaData throws exception for invalid file
     */
    public function testFilelinkGetMetadataException()
    {
        $mock_response = new MockHttpResponse(
            400,
            'Bad Request'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $filelink = new Filelink("some-bad-file-handle",
                        self::TEST_API_KEY, $stub_http_client);

        $result = $filelink->getMetaData();
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

        $filelink = new Filelink($this->test_file_handle,
                        self::TEST_API_KEY, $stub_http_client);
        $new_filelink = $filelink->store();
        $this->assertNotNull($new_filelink);
    }
}