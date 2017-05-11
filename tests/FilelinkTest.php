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
     * Test Filelink get Signed Url
     */
    public function testFilelinkSignedUrl()
    {
        $filelink = new Filelink($this->test_file_handle,
            $this->test_api_key);

        $expected_url = sprintf('%s?policy=%s&signature=%s',
            $filelink->url(),
            $this->test_security->policy,
            $this->test_security->signature
        );

        $signed_url = $filelink->signedUrl($this->test_security);
        $this->assertEquals($expected_url, $signed_url);
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

        $filelink = new Filelink($this->test_file_handle, $this->test_api_key,
                        $this->test_security, $stub_http_client);

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

        $filelink = new Filelink($this->test_file_handle, $this->test_api_key,
                        $this->test_security, $stub_http_client);

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

        $filelink = new Filelink($this->test_file_handle, $this->test_api_key,
                        $this->test_security, $stub_http_client);
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

        $filelink = new Filelink('some-bad-file-handle', $this->test_api_key,
                        $this->test_security, $stub_http_client);
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

        $filelink = new Filelink($this->test_file_handle, $this->test_api_key,
                        $this->test_security, $stub_http_client);

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

        $filelink = new Filelink('some-bad-file-handle', $this->test_api_key,
                        $this->test_security, $stub_http_client);

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

        $filelink = new Filelink('gQNI9RF1SG2nRmvmQDMU', $this->test_api_key,
                        $this->test_security, $stub_http_client);
        $result = $filelink->delete();

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

        $filelink = new Filelink('gQNI9RF1SG2nRmvmQDMU', $this->test_api_key,
            $this->test_security, $stub_http_client);

        $result = $filelink->delete();
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

        $filelink = new Filelink($this->test_file_handle, $this->test_api_key,
                        $this->test_security, $stub_http_client);

        $result = $filelink->overwrite($this->test_filepath);

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

        $filelink = new Filelink('some-bad-file-handle',
                        $this->test_api_key,
                        $this->test_security,
                        $stub_http_client);

        $result = $filelink->overwrite($this->test_filepath);

        $this->assertTrue($result);
    }

    /**
     * Test calling store() API failed throws exception
     */
    public function testFilelinkSaveException()
    {
        $mock_response = new MockHttpResponse(400,
            'Invalid parameters');

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $filelink = new Filelink('some-bad-file-handle',
                        $this->test_api_key,
                        $this->test_security,
                        $stub_http_client);

        $result = $filelink->save();
    }

    /**
     * Test zipping the content of a chained call
     */
    public function testFilelinkZipSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            new MockHttpResponseBody('some content')
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $filelink = new Filelink($this->test_file_handle,
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $destination = __DIR__ . '/testfiles/my-zipped-transformed-file.zip';
        $transformed_filelink = $filelink->rotate('00FF00', 45)
                ->zip()
                ->download($destination);

        $this->assertNotNull($transformed_filelink);
    }
}
