<?php
namespace Filestack\Test;

use Filestack\Filelink;
use Filestack\FilestackSecurity;
use Filestack\FilestackException;

class FilelinkTest extends BaseTest
{
    /**
     * Test initializing Filelink intialized with handle and API Key
     */
    public function testFilelinkInitialized()
    {
        $filelink = new Filelink($this->test_file_handle, $this->test_api_key);
        $this->assertEquals($filelink->handle, $this->test_file_handle);
        $this->assertEquals($filelink->api_key, $this->test_api_key);
    }

    /**
     * Test filelink get content
     */
    public function testFilelinkGetContentSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            new MockHttpResponseBody("some content")
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key, $stub_http_client);

        $result = $filelink->getContent();

        $this->assertNotNull($result);
    }

    /**
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
                        $this->test_api_key, $stub_http_client);

        $result = $filelink->getContent();
    }

    /**
     * Test downloading a filelink
     */
    public function testFilelinkDownloadSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            'some file content'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key, $stub_http_client);
        $destination = __DIR__ . "/testfiles/my-custom-filenamed.jpg";

        $result = $filelink->download($destination);
        $this->assertTrue($result);
    }

    /**
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
                        $this->test_api_key, $stub_http_client);
        $destination = __DIR__ . "/testfiles/test.jpg";

        $result = $filelink->download($destination);
    }

    /**
     * Test getting meta data of a filelink
     */
    public function testFilelinkGetMetadataSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{"filename": "somefile.jpg"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key, $stub_http_client);

        $result = $filelink->getMetaData();
        $this->assertNotNull($result['filename']);
    }

    /**
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
                        $this->test_api_key, $stub_http_client);

        $result = $filelink->getMetaData();
    }

    /**
     * Test deleting a filelink
     */
    public function testFilelinkDeleteSuccess()
    {
        $mock_response = new MockHttpResponse(200);

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $security = new FilestackSecurity($this->test_secret);
        $filelink = new Filelink('gQNI9RF1SG2nRmvmQDMU',
                        $this->test_api_key, $stub_http_client);
        $result = $filelink->delete($security);

        $this->assertTrue($result);
    }

    /**
     * Test deleting a filelink throws exception on error
     */
    public function testFilelinkDeleteException()
    {
        $mock_response = new MockHttpResponse(404);

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(404);

        $security = new FilestackSecurity($this->test_secret);
        $filelink = new Filelink('gQNI9RF1SG2nRmvmQDMU',
                        $this->test_api_key, $stub_http_client);
        $result = $filelink->delete($security);
    }

    /**
     * Test storing a filelink
     */
    public function testFilelinkStoreSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{url: "https://cdn.filestack.com/somefilehandle"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key, $stub_http_client);

        $new_filelink = $filelink->store();

        $this->assertNotNull($new_filelink);
    }

    /**
     * Test storing a filelink
     */
    public function testFilelinkStoreException()
    {
        $mock_response = new MockHttpResponse(403);

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(403);

        $filelink = new Filelink($this->test_file_handle,
                        'some-bad-key', $stub_http_client);

        $new_filelink = $filelink->store();
    }

    /**
     * Test overwriting a filelink
     */
    public function testFilelinkOverwriteSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{url: "https://cdn.filestack.com/somefilehandle"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $security = new FilestackSecurity($this->test_secret);
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key, $stub_http_client);

        $result = $filelink->overwrite($this->test_filepath, $security);

        $this->assertTrue($result);
    }

    /**
     * Test overwriting a filelink
     */
    public function testFilelinkOverwriteException()
    {
        $mock_response = new MockHttpResponse(404);

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(404);

        $security = new FilestackSecurity($this->test_secret);
        $filelink = new Filelink('some-bad-file-handle',
                        $this->test_api_key, $stub_http_client);

        $result = $filelink->overwrite($this->test_filepath, $security);

        $this->assertTrue($result);
    }
}
