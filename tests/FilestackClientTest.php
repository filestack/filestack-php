<?php
namespace Filestack\Tests;

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
        $client->getContent('some-bad-file-handle-testing');
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
     * Test getting sfw (safe for work) flag of a filelink
     */
    public function testGetSfwSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{"sfw": true}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );
        $result_json = $client->getSafeForWork($this->test_file_handle);
        $this->assertNotNull($result_json);
    }

    /**
     * Test getting sfw (safe for work) failed with exception
     */
    public function testGetSfwException()
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
        $client->getSafeForWork($this->test_file_handle);
    }

    /**
     * Test getting tags of a filelink
     */
    public function testGetTagsSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{"tags": "sometags"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );
        $result_json = $client->getTags($this->test_file_handle);

        $this->assertNotNull($result_json);
    }

    /**
     * Test getting tags failed
     */
    public function testGetTagsException()
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
        $client->getTags($this->test_file_handle);
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
        $client->getMetaData('some-bad-file-handle-testing');
    }

    /**
     * Test calling collage() function successfully
     */
    public function testCollageSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{"url": "http://cdn.filestackcontent.com/somehandle",'.
            '"filename": "somefile.jpg",'.
            '"size": "1000",'.
            '"mimetype": "image/jpg"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $sources = [
            '9K1BZLt6SAyztVaOtAQ4',
            'FWOrzDcpREanJDI3hdR5',
            'Vi6RUEi6TgCSo9FXYVxP',
            'https://d1wtqaffaaj63z.cloudfront.net/images/E-0510.JPG'
        ];

        $width = 800;
        $height = 600;
        $color = 'white';
        $fit = 'auto';
        $margin = 10;
        $auto_rotate = false;
        $store_options = ['filename' => 'mycollage_file.png'];

        $filelink = $client->collage($sources, $width, $height, $store_options,
                            $color, $fit, $margin, $auto_rotate);

        $this->assertNotNull($filelink);
    }

    /**
     * Test calling convertFile() function successfully
     */
    public function testconvertFileSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            $this->mock_response_json
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $source = '9K1BZLt6SAyztVaOtAQ4';
        $output_options = [
            'background' => 'white',
            'density' => 50,
            'compress' => true,
            'colorspace' => 'input',
            'quality' => 80,
            'strip' => true,
            'pageformat' => 'letter',
            'pageorientation' => 'landscape'
        ];

        $filelink = $client->convertFile($source, 'pdf', $output_options);

        $this->assertNotNull($filelink);
    }

    /**
     * Test calling convertAudio() function successfully
     */
    public function testconvertAudioSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{"uuid" : "some_uuid"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $source = 'https://upload.wikimedia.org/wikipedia/commons/b/b5/'.
            'Op.14%2C_Scherzo_No.2_in_C_minor_for_piano%2C_C._Schumann.ogg';
        $output_options = [
            'access'                => 'public',
            'audio_bitrate'         => 256,
            'audio_channels'        => 2,
            'audio_sample_rate'     => 44100,
            'force'                 => true,
            'title'                 => 'test Filestack Audio conversion'
        ];

        $uuid = $client->convertAudio($source, 'mp3', $output_options);

        $this->assertNotNull($uuid);
    }

    /**
     * Test calling convertVideo() function successfully
     */
    public function testconvertVideoSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{"uuid" : "some_uuid", "conversion_url": "http://someurl.com/handle"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $source = 'Q5eBTKldRfCSuEjUYuAz';
        $output_options = [
            'access'                => 'public',
            'aspect_mode'           => 'letterbox',
            'audio_bitrate'         => 256,
            'audio_channels'        => 2,
            'audio_sample_rate'     => 44100,
            'fps'                   => 60,
            'title'                 => 'test Filestack Audio conversion',
            'video_bitrate'         => 1024,
            'watermark_top'         => 10,
            'watermark_url'         => 'Bc2FQwXReueTsaeXB6rO'
        ];

        $force = true;
        $result = $client->convertVideo($source, 'm4a', $output_options, $force);
        $info = $client->getConvertTaskInfo($result['conversion_url']);

        $this->assertNotNull($info);
    }

    /**
     * Test calling convertVideo() throws exception
     */
    public function testconvertVideoThrowsException()
    {
        $mock_response = new MockHttpResponse(
            404,
            'file not found'
        );

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(404);

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $source = 'Q5eBTKldRfCSuEjUYuAz';
        $output_options = [];
        $client->convertVideo($source, 'm4a', $output_options);
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
        $client->delete($test_handle);
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

        // test downloading to directory
        $destination = __DIR__ . '/testfiles/';
        $result2 = $client->download($this->test_file_url, $destination);

        $this->assertTrue($result2);
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
        $client->download('some-bad-file-handle-testing', $destination);
    }

    /**
     * Test calling the screenshot function successfully
     */
    public function testScreenshotSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            $this->mock_response_json
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $url = 'https://en.wikipedia.org/wiki/Main_Page';
        $store_options = ['filename' => 'myscreenshot_file.png'];

        $filelink = $client->screenshot($url, $store_options);
        $this->assertNotNull($filelink);
    }

    /**
     * Test calling the screenshot function successfully
     */
    public function testScreenshotWithDest()
    {
        $mock_response = new MockHttpResponse(
            200,
            $this->mock_response_json
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $url = 'https://en.wikipedia.org/wiki/Main_Page';
        $filelink = $client->screenshot($url);

        $this->assertNotNull($filelink);
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
        $client->uploadUrl($this->test_filepath);
    }

    /**
     * Test calling the upload function with a valid api key.
     */
    public function testUploadSuccess()
    {
        $test_headers = ['ETag' =>['some-etag']];
        $mock_response = new MockHttpResponse(200,
            json_encode([
                'filename'  => 'somefilename.jpg',
                'size'      => '1000',
                'type'      => 'image/jpg',
                'url'       => 'https://cdn.filestack.com/somefilehandle',
                'uri'       => 'https://uploaduri/handle&partNum=1',
                'region'    => 'us-east-1',
                'upload_id' => 'test-upload-id',
                'headers'   => []
            ]),
            $test_headers
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
        $test_headers = ['ETag' =>['some-etag']];
        $mock_response = new MockHttpResponse(200,
            json_encode([
                'filename'  => 'somefilename.jpg',
                'size'      => '1000',
                'type'      => 'image/jpg',
                'url'       => 'https://cdn.filestack.com/somefilehandle',
                'uri'       => 'https://uploaduri/handle&partNum=1',
                'region'    => 'us-east-1',
                'upload_id' => 'test-upload-id',
                'headers'   => []
            ]),
            $test_headers
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $options = [
            'location' => 's3',
            'filename' => 'php-testfile.txt',
            'mimetype' => 'text/plain'
        ];

        $filelink = $client->upload($this->test_filepath, $options);
        $this->assertNotNull($filelink);
    }

    /**
     * Test uploading using intelligent ingestion flow
     */
    public function testUploadIntelligentSuccess()
    {
        $mock_response_202 = new MockHttpResponse(202,  '{accepted: true}');
        $mock_response = new MockHttpResponse(200,
            json_encode([
                'filename'  => 'somefilename.jpg',
                'size'      => '1000',
                'type'      => 'image/jpg',
                'url'       => 'https://cdn.filestack.com/somefilehandle',
                'uri'       => 'https://uploaduri/handle&partNum=1',
                'region'    => 'us-east-1',
                'upload_id' => 'test-upload-id',
                'headers'   => [],
                'upload_type' => 'intelligent_ingestion' // intelligent flag
            ])
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
            ->willReturnOnConsecutiveCalls(
                $mock_response,
                $mock_response,
                $mock_response,
                $mock_response_202,
                $mock_response,
                $mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $filelink = $client->upload($this->test_filepath, ['intelligent' => true]);
        $this->assertNotNull($filelink);
    }

    /**
     * Test calling the upload function throws exception if file not found
     */
    public function testUploadFileNotFound()
    {
        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );
        $client->upload('/some/bad/filepath');
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

        $filelink = $client->uploadUrl($this->test_file_url, [
            'location' => 's3',
            'filename' => 'filestack-php-sdk-test.jpg'
        ]);

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

        $client->overwrite(
            $this->test_filepath,
            'some-bad-file-handle-testing'
        );
    }

    /**
     * Test overwriting a Filestack File with external url
     */
    public function testOverwriteUrlSuccess()
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

        $filelink = $client->overwrite($this->test_file_url, $this->test_file_handle);
        $this->assertNotNull($filelink);
    }

    /**
     * Test transforming an external source
     */
    public function testTransformSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            $this->mock_response_json
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

        $filelink = $client->transform($url, $transform_tasks);

        $this->assertNotNull($filelink);
    }

    /**
     * Test debugging a transformation url of a chained call
     */
    public function testDebuggingTransformCalls()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{"apikey": "someapikey", "errors": "some errors"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $transform_tasks = [
            'resize'    => ['w' => '100', 'h' => '100'],
            'detect_faces'    => []
        ];

        $json_response = $client->debug($this->test_file_handle, $transform_tasks);

        $this->assertNotNull($json_response);
    }

    /**
     * Test debugging a transformation url of a chained call
     */
    public function testDebuggingThrowsException()
    {
        $mock_response = new MockHttpResponse(
            400,
            'invalid attr value'
        );

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $transform_tasks = [
            'resize'    => ['w' => 'test-value']
        ];

        $client->debug($this->test_file_handle, $transform_tasks);
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
        $client->someInvalidMethod('test');
    }

    /**
     * Test zipping files successfully
     */
    public function testZipSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            $this->mock_response_json
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $sources = [
            'https://d1wtqaffaaj63z.cloudfront.net/images/20150617_143146.jpg',
            $this->test_file_handle
        ];

        $store_options = ['filename' => 'mycollage_file.png'];
        $filelink = $client->zip($sources, $store_options);
        $this->assertNotNull($filelink);
    }

    /**
     * Test zipping files throw exceptions if file not found
     */
    public function testZipFilesNotFound()
    {
        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(404);

        $mock_response = new MockHttpResponse(
            404,
            'File not found'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $client = new FilestackClient(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $sources = [
            'some-bad-file-handle-or-url'
        ];

        $client->zip($sources);
    }
}
