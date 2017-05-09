<?php
namespace Filestack\Test;

use Filestack\FilestackConfig;
use Filestack\Filelink;
use Filestack\FilestackSecurity;
use Filestack\FilestackException;

class FilestackTransformTest extends BaseTest
{
    /**
     * Test the transform method on a Filelink returning contents
     */
    public function testTranformationSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            new MockHttpResponseBody("some content")
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $transform_tasks = [
            'crop'      => ['dim' => '[10,20,200,250]'],
            'resize'    => ['w' => '100', 'h' => '100'],
            'rotate'    => ['b' => '00FF00', 'd' => '45']
        ];

        $filelink = new Filelink($this->test_file_handle, $this->test_api_key,
                        $this->test_security, $stub_http_client);

        $contents = $filelink->transform($transform_tasks);
        $this->assertNotNull($contents);
    }

    /**
     * Test the transform method on a Filelink saving to a location
     */
    public function testTranformationWithDest()
    {
        $mock_response = new MockHttpResponse(
            200,
            new MockHttpResponseBody("some content")
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $transform_tasks = [
            'crop'      => ['dim' => '[10,20,200,250]']
        ];

        $filelink = new Filelink($this->test_file_handle, $this->test_api_key,
                        $this->test_security, $stub_http_client);

        $destination = __DIR__ . '/testfiles/my-transformed-file.jpg';
        $success = $filelink->transform($transform_tasks, $destination);

        $this->assertTrue($success);
    }

    /**
     * Test the transform method throws exception if api call failed
     */
    public function testTranformationFailed()
    {
        $mock_response = new MockHttpResponse(
            403,
            'Forbidden, Missing credentials'
        );

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(403);

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $transform_tasks = [
            'resize'    => ['w' => '100', 'h' => '100']
        ];

        $filelink = new Filelink($this->test_file_handle, $this->test_api_key,
                        $this->test_security, $stub_http_client);
        $contents = $filelink->transform($transform_tasks);
    }

    /**
     * Test the transform method throws exception if task is invalid
     */
    public function testTranformInvalidTaskThrowsException()
    {
        $mock_response = new MockHttpResponse(
            400,
            'Invalid transformation task'
        );

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $transform_tasks = [
            'some_bad_task'    => ['w' => '100', 'h' => '100']
        ];

        $filelink = new Filelink($this->test_file_handle, $this->test_api_key,
                        $this->test_security, $stub_http_client);
        $contents = $filelink->transform($transform_tasks);
    }

    /**
     * Test getting a transformation url of a chained call
     */
    public function testTranformChainUrl()
    {
        $filelink = new Filelink($this->test_file_handle, $this->test_api_key);
        $filelink->crop(10, 20, 200, 250)
                ->rotate('00FF00', 45);

        $expected_url = sprintf('%s/%s/%s',
            FilestackConfig::PROCESSING_URL,
            'crop=dim:%5B10%2C20%2C200%2C250%5D/rotate=b:00FF00,d:45,e:false',
            $filelink->handle
        );
        $this->assertEquals($expected_url, $filelink->transform_url);

        $expected_url = sprintf('%s/%s/%s',
            FilestackConfig::PROCESSING_URL,
            'resize=w:100,h:100,f:clip,a:center',
            $filelink->handle
        );

        $filelink->resetTransform();
        $filelink->resize(100, 100);
        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test getting a transformation url of a chained call
     */
    public function testTranformInvalidMethod()
    {
        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $filelink = new Filelink($this->test_file_handle, $this->test_api_key);
        $filelink->crop_bad_method(10, 20, 200, 250);
    }

    /**
     * Test chaining transformation with getContent call
     */
    public function testTranformChainGetContent()
    {
        $mock_response = new MockHttpResponse(
            200,
            new MockHttpResponseBody("some content")
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $filelink = new Filelink($this->test_file_handle,
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $contents = $filelink->crop(10, 20, 200, 250)
                ->rotate('green', 45)
                ->getTransformedContent();

        $this->assertNotNull($contents);
    }

    /**
     * Test chaining transformation with download call
     */
    public function testTranformChainDownload()
    {
        $mock_response = new MockHttpResponse(
            200,
            new MockHttpResponseBody("some content")
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $filelink = new Filelink($this->test_file_handle,
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $destination = __DIR__ . '/testfiles/my-transformed-file.jpg';

        $result = $filelink
                        ->resize(100, 100)
                        ->downloadTransformed($destination);

        $this->assertTrue($result);
    }

    /**
     * Test chaining transformation with download call
     */
    public function testTranformChainStore()
    {
        $mock_response = new MockHttpResponse(
            200,
            json_encode([
                'filename'  => 'somefilename.jpg',
                'size'      => '1000',
                'type'      => 'image/jpg',
                'url'       => 'https://cdn.filestack.com/somefilehandle'
            ])
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $filelink = new Filelink($this->test_file_handle,
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $transformed_filelink = $filelink
                        ->sepia()
                        ->circle()
                        ->blur(20)
                        ->store();

        $this->assertNotNull($transformed_filelink);
        $this->assertEquals($transformed_filelink->metadata['filename'], 'somefilename.jpg');
    }
}
