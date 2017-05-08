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
     * Test getting a transformation url of a chained call
     */
    public function testTranformChainUrl()
    {
        $filelink = new Filelink($this->test_file_handle, $this->test_api_key);

        $crop_options = ['dim' => '[10,20,200,250]'];
        $rotate_options = ['b' => '00FF00', 'd' => '45'];
        $resize_options = ['w' => '100', 'h' => '100'];

        $filelink->crop($crop_options)
                ->rotate($rotate_options);

        $expected_url = sprintf('%s/%s/%s',
            FilestackConfig::PROCESSING_URL,
            'crop=dim:%5B10%2C20%2C200%2C250%5D/rotate=b:00FF00,d:45',
            $filelink->handle
        );
        $this->assertEquals($filelink->transform_url, $expected_url);

        $expected_url = sprintf('%s/%s/%s',
            FilestackConfig::PROCESSING_URL,
            'resize=w:100,h:100',
            $filelink->handle
        );

        $filelink->resetTransform();
        $filelink->resize($resize_options);
        $this->assertEquals($filelink->transform_url, $expected_url);
    }

    /**
     * Test getting a transformation url of a chained call
     */
    public function testTranformInvalidMethod()
    {
        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $filelink = new Filelink($this->test_file_handle, $this->test_api_key);
        $crop_options = ['dim' => '[10,20,200,250]'];
        $filelink->crop_bad_method($crop_options);
    }

    /**
     * Test getting a transformation url of a chained call
     */
    public function testTranformInvalidAttributes()
    {
        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $filelink = new Filelink($this->test_file_handle,
            $this->test_api_key,
            $this->test_security
        );

        $crop_options = ['some_bad_attribute' => 'some_bad_values'];
        $content = $filelink->crop($crop_options)->getTransformedContent();
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

        $crop_options = ['dim' => '[10,20,200,250]'];
        $rotate_options = ['b' => '00FF00', 'd' => '45'];

        $is_transformation = true;

        $contents = $filelink->crop($crop_options)
                ->rotate($rotate_options)
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

        $resize_options = ['w' => '100', 'h' => '100'];

        $destination = __DIR__ . '/testfiles/my-transformed-file.jpg';
        $is_transformation = true;

        $result = $filelink
                        ->resize($resize_options)
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
                        ->blur(['amount' => '20'])
                        ->store();

        $this->assertNotNull($transformed_filelink);
        $this->assertEquals($transformed_filelink->metadata['filename'], 'somefilename.jpg');
    }
}
