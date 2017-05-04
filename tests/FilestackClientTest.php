<?php
use Filestack\FilestackClient;
use Filestack\FilestackSecurity;
use Filestack\FilestackException;

class FilestackClientTest extends \PHPUnit_Framework_TestCase
{
    const TEST_API_KEY = 'A5lEN6zU8SemSBWiwcGJhz';
    const TEST_SECRET = '3UAQ64UWMNCCRF36CY2NSRSPSU';

    protected $test_filepath;
    protected $test_file_url;
    protected $test_file_handle;

    protected function setUp()
    {
        $this->test_filepath = __DIR__ . '/testfiles/calvinandhobbes.jpg';
        $this->test_file_url = 'https://cdn.filestackcontent.com/IIkUk9D8TWKHldxmMVRt';
        $this->test_file_handle = 'IIkUk9D8TWKHldxmMVRt';
    }

    public function tearDown()
    {
        // teardown calls
    }

    /**
     * Test initializing FilestackClient with an API Key
     */
    public function testClientInitialized()
    {
        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $client = new FilestackClient(self::TEST_API_KEY, $stub_http_client);
        $this->assertEquals($client->api_key, self::TEST_API_KEY);
    }

    /**
     * Test getting content of Filestack file
     */
    public function testGetContentSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            new MockHttpResponseBody('some content')
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(self::TEST_API_KEY, $stub_http_client);
        $result = $client->getContent($this->test_file_url);

        $this->assertNotNull($result);
    }

    /**
     * Test get content throws exception for invalid file
     */
    public function testGetContentNotFound()
    {
        $mock_response = new MockHttpResponse(
            404,
            'File not found'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(404);

        $client = new FilestackClient(self::TEST_API_KEY, $stub_http_client);
        $result = $client->getContent('some-bad-file-handle-testing');
    }

    /**
     * Test getting metadata of a filestack file
     */
    public function testGetMetaDataSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{"filename": "somefilename.jpg"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(self::TEST_API_KEY, $stub_http_client);
        $result_json = $client->getMetaData($this->test_file_url);
        $this->assertEquals($result_json['filename'], 'somefilename.jpg');
    }

    /**
     * Test getting metadata throws exception for invalid file
     */
    public function testGetMetadataException()
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

        $client = new FilestackClient(self::TEST_API_KEY, $stub_http_client);
        $filelink = $client->getMetaData('some-bad-file-handle-testing');
    }

    /**
     * Test deleting a Filestack File
     */
    public function testDeleteSuccess()
    {
        $mock_response = new MockHttpResponse(200);

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $security = new FilestackSecurity(self::TEST_SECRET);
        $client = new FilestackClient(self::TEST_API_KEY, $stub_http_client);

        $test_handle = 'gQNI9RF1SG2nRmvmQDMU';
        $result = $client->delete($test_handle, $security);

        $this->assertTrue($result);
    }

    /**
     * Test deleting a Filestack File throws exception if not found
     */
    public function testDeleteException()
    {
        $mock_response = new MockHttpResponse(404);

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(404);

        $security = new FilestackSecurity(self::TEST_SECRET);
        $client = new FilestackClient(self::TEST_API_KEY, $stub_http_client);

        $test_handle = 'some-bad-file-handle-testing';
        $result = $client->delete($test_handle, $security);
    }

    /**
     * Test downloading a Filestack File
     */
    public function testDownloadSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            'some file content'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $destination = __DIR__ . '/testfiles/my-custom-filename.jpg';

        $client = new FilestackClient(self::TEST_API_KEY, $stub_http_client);
        $result = $client->download($this->test_file_url, $destination);

        $this->assertTrue($result);
    }

    /**
     * Test downloading exception with file not found
     */
    public function testDownloadNotFound()
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

        $destination = __DIR__ . '/testfiles/my-custom-filename.jpg';

        $client = new FilestackClient(self::TEST_API_KEY, $stub_http_client);
        $result = $client->download('some-bad-file-handle-testing', $destination);
    }

    /**
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

        $client = new FilestackClient('some_bad_key', $stub_http_client);
        $filelink = $client->store($this->test_filepath);
    }

    /**
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

        $client = new FilestackClient(self::TEST_API_KEY, $stub_http_client);
        $filelink = $client->store($this->test_filepath);

        $this->assertNotNull($filelink);
    }

    /**
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

        $client = new FilestackClient(self::TEST_API_KEY, $stub_http_client);

        $security = null;
        $extras = [
            'Location' => 'dropbox',
            'Filename' => 'somefilename.jpg',
        ];

        $filelink = $client->store($this->test_filepath);

        $this->assertNotNull($filelink);
    }

    /**
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

        $client = new FilestackClient(self::TEST_API_KEY, $stub_http_client);
        $filelink = $client->store($this->test_file_url);

        $this->assertNotNull($filelink);
    }

    /**
     * Test overwriting a Filestack File
     */
    public function testOverwriteSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{url: "https://cdn.filestack.com/somefilehandle"}');

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $security = new FilestackSecurity(self::TEST_SECRET);
        $client = new FilestackClient(self::TEST_API_KEY, $stub_http_client);

        $filelink = $client->overwrite(
                        $this->test_filepath,
                        $this->test_file_handle,
                        $security
                    );

        $this->assertNotNull($filelink);
    }

    /**
     * Test overwriting file throws exception if invalid url
     */
    public function testOverwriteException()
    {
        $mock_response = new MockHttpResponse(
            404,
            'file not found');

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(404);

        $security = new FilestackSecurity(self::TEST_SECRET);
        $client = new FilestackClient(self::TEST_API_KEY, $stub_http_client);

        $filelink = $client->overwrite(
                        $this->test_filepath,
                        'some-bad-file-handle-testing',
                        $security
                    );
    }
}
