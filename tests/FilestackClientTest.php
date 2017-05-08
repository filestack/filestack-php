<?php
namespace Filestack\Test;

use Filestack\FilestackClient;
use Filestack\FilestackSecurity;
use Filestack\FilestackException;

class FilestackClientTest extends BaseTest
{
    /**
     * Test initializing FilestackClient with an API Key
     */
    public function testClientInitialized()
    {
        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );
        $this->assertEquals($client->api_key, $this->test_api_key);
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

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );
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

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client);
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

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );
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

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );
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

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $test_handle = 'gQNI9RF1SG2nRmvmQDMU';
        $result = $client->delete($test_handle);

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

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $test_handle = 'some-bad-file-handle-testing';
        $result = $client->delete($test_handle);
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

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );
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

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );
        $result = $client->download('some-bad-file-handle-testing', $destination);
    }

    /**
     * Test calling the upload function with an invalid api key.
     */
    public function testUploadInvalidKey()
    {
        $mock_response = new MockHttpResponse(403);
        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
            ->willReturn($mock_response);

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(403);

        $client = new FilestackClient(
            'some-bad-key',
            $this->test_security,
            $stub_http_client
        );
        $filelink = $client->upload($this->test_filepath);
    }

    /**
     * Test calling the upload function with a valid api key.
     */
    public function testUploadSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{url: "https://cdn.filestack.com/somefilehandle"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );
        $filelink = $client->upload($this->test_filepath);

        $this->assertNotNull($filelink);
    }

    /**
     * Test calling the upload function with a valid api key and options
     */
    public function testUploadSuccessWithOptions()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{url: "https://cdn.filestack.com/somefilehandle"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $extras = [
            'Location' => 'dropbox',
            'Filename' => 'somefilename.jpg',
        ];

        $filelink = $client->upload($this->test_filepath);

        $this->assertNotNull($filelink);
    }

    /**
     * Test calling the upload function with a valid api key and URL of file
     */
    public function testUploadUrlSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{url: "https://cdn.filestack.com/somefilehandle"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );
        $filelink = $client->upload($this->test_file_url);

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

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $filelink = $client->overwrite($this->test_filepath, $this->test_file_handle);
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

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $filelink = $client->overwrite(
                        $this->test_filepath,
                        'some-bad-file-handle-testing'
                    );
    }

    /**
     * Test transforming an external source
     */
    public function testTransformSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            new MockHttpResponseBody('some content')
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $url = "https://cdn.filestackcontent.com/vA9vFnjRVGmEbNPy3beQ";
        $transform_tasks = [
            'crop'      => ['dim' => '[10,20,200,250]'],
            'resize'    => ['w' => '100', 'h' => '100'],
            'rotate'    => ['b' => '00FF00', 'd' => '45']
        ];

        $content = $client->transform($url, $transform_tasks);

        $this->assertNotNull($content);
    }

    /**
     * Test transforming an external source
     */
    public function testTransformWithDest()
    {
        $mock_response = new MockHttpResponse(
            200,
            new MockHttpResponseBody('some content')
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $url = "https://cdn.filestackcontent.com/vA9vFnjRVGmEbNPy3beQ";
        $transform_tasks = [
            'crop'      => ['dim' => '[10,20,200,250]']
        ];

        $destination = __DIR__ . '/testfiles/transform-with-dest.jpg';
        $content = $client->transform($url, $transform_tasks, $destination);

        $this->assertNotNull($content);
    }

    /**
     * Test transforming an external source
     */
    public function testTransformStoreSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            new MockHttpResponseBody(json_encode([
                    'filename'  => 'somefilename.jpg',
                    'size'      => '1000',
                    'type'      => 'image/jpg',
                    'url'       => 'https://cdn.filestack.com/somefilehandle'
                ])
            )
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $url = "https://cdn.filestackcontent.com/vA9vFnjRVGmEbNPy3beQ";
        $transform_tasks = [
            'crop'      => ['dim' => '[10,20,200,250]'],
            'resize'    => ['w' => '100', 'h' => '100'],
            'rotate'    => ['b' => '00FF00', 'd' => '45'],
            'store'     => []
        ];

        $result = $client->transform($url, $transform_tasks);
        $json = json_decode($result);

        $this->assertNotNull($result);
        $this->assertEquals($json->filename, 'somefilename.jpg');
    }

    /**
     * Test invalid function call throws exception
     */
    public function testInvalidMethodThrowExceptions()
    {
        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security);
        $result = $client->someInvalidMethod('test');
    }
}
