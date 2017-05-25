<?php
namespace Filestack\Tests;

use Filestack\FilestackClient;
use Filestack\FilestackSecurity;
use Filestack\FilestackException;

class CommonMixinTest extends BaseTest
{
    /**
     * Test multipart upload start
     */
    public function testMultipartUploadStart()
    {
        $expected_upload_id = 'some_upload_id';
        $mock_response = new MockHttpResponse(
            200,
            '{"upload_id": "' . $expected_upload_id . '"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $metadata = [
            'filepath' => $this->test_filepath,
            'filename' => 'somefile',
            'filesize' => 1024,
            'mimetype' => 'image/jpg',
            'location' => 's3'
        ];

        $common_mixin = new MockCommonMixin($this->test_api_key,
            $this->test_security,
            $stub_http_client);

        $json = $common_mixin->sendMultipartStart($this->test_api_key,
            $metadata, $this->test_security);

        $this->assertEquals($expected_upload_id, $json['upload_id']);
    }

    /**
     * Test multipart upload start throws exception
     */
    public function testMultipartStartException()
    {
        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $expected_upload_id = 'some_upload_id';
        $mock_response = new MockHttpResponse(
            400,
            'bad data format'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $metadata = [
            'filepath' => $this->test_filepath,
            'filename' => 'somefile',
            'filesize' => 1024,
            'mimetype' => 'image/jpg',
            'location' => 's3'
        ];

        $common_mixin = new MockCommonMixin($this->test_api_key,
            $this->test_security,
            $stub_http_client);

        $json = $common_mixin->sendMultipartStart($this->test_api_key,
            $metadata, $this->test_security);
    }

    /**
     * Test handling fulfilled promises from multipart upload calls
     */
    public function testAppendS3Promises()
    {
        $mock_response = new MockHttpResponse(
            200,
            json_encode([
                "url"       => "https://s3.amazon.com/bucket?partNumber=1",
                'headers'   => [ 'auth-key' => 'somekey']
            ])
        );

        $part_num = 1;
        $jobs[$part_num] = [
            'api_key'       => $this->test_api_key,
            'seek_point'    => 0,
            'md5'           => 'md5-hash',
            'part_num'      => $part_num,
            'chunksize'     => 1024,
            'uri'           => 'https://upload_api/uri',
            'region'        => 'us-east-1',
            'upload_id'     => 'some-upload-id',
            'filepath'      => $this->test_filepath,
            'filename'      => 'somefile',
            'filesize'      => 1024,
            'mimetype'      => 'text',
            'location'      => 's3'
        ];

        $upload_results = [
            ['value' => $mock_response]
        ];
        $s3_promises = [];

        $mock_response = new MockHttpResponse(
            200,
            new MockHttpResponseBody('some content')
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $common_mixin = new MockCommonMixin($this->test_api_key,
            $this->test_security,
            $stub_http_client);

        $common_mixin->callAppendS3Promises($jobs, $upload_results, $s3_promises);

        $this->assertNotEmpty($s3_promises);
    }

    /**
     * Test getting eTags from fulfilled s3 promises from multipart upload calls
     */
    public function testMultipartGetTags()
    {
        $mock_response = new MockHttpResponse(
            200,
            new MockHttpResponseBody('some content'),
            ['ETag' => ['tag1']]
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $s3_results = [
            ['value' => $mock_response]
        ];
        $parts_etags = [];

        $common_mixin = new MockCommonMixin($this->test_api_key,
            $this->test_security,
            $stub_http_client);

        $common_mixin->callMultipartGetTags($s3_results, $parts_etags);

        $this->assertNotEmpty($parts_etags);
    }

    /**
     * Test getting eTags from multipart upload calls throws Exception
     */
    public function testMultipartGetTagsThrowException()
    {
        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(500);

        $mock_response = new MockHttpResponse(200);
        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $s3_results = [['some-param' => 'some-value']];
        $parts_etags = [];

        $common_mixin = new MockCommonMixin($this->test_api_key,
            $this->test_security,
            $stub_http_client);

        $common_mixin->callMultipartGetTags($s3_results, $parts_etags);
    }

    /**
     * Test calling multipart upload completed
     */
    public function testMultipartSendMultipartJobs()
    {
        $mock_response = new MockHttpResponse(
            200,
            $this->mock_response_json
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $common_mixin = new MockCommonMixin($this->test_api_key,
            $this->test_security,
            $stub_http_client);

        for($i=1; $i<32;$i++) {
            $jobs[$i] = [
                'api_key'       => $this->test_api_key,
                'seek_point'    => 0,
                'md5'           => 'md5-hash',
                'part_num'      => $i,
                'chunksize'     => 1024,
                'uri'           => 'https://upload_api/uri',
                'region'        => 'us-east-1',
                'upload_id'     => 'some-upload-id',
                'filepath'      => $this->test_filepath,
                'filename'      => 'somefile',
                'filesize'      => 1024,
                'mimetype'      => 'text',
                'location'      => 's3'
            ];
        }

        $etags = $common_mixin->sendMultipartJobs($jobs, $this->test_security);
        $this->assertNotNull($etags);
    }

    /**
     * Test calling multipart upload completed
     */
    public function testMultipartComplete()
    {
        $mock_response = new MockHttpResponse(
            200,
            $this->mock_response_json
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $upload_data = [
            'uri'       => 'https://uploadapi.com/uri',
            'region'    => 'us-east-1',
            'upload_id' => 'test-upload-id'
        ];

        $metadata = [
            'filepath' => $this->test_filepath,
            'filename' => 'somefile',
            'filesize' => 1024,
            'mimetype' => 'image/jpg',
            'location' => 's3'
        ];

        $parts = '1:tag1;2:tag2';

        $common_mixin = new MockCommonMixin($this->test_api_key,
            $this->test_security,
            $stub_http_client);

        $filelink = $common_mixin->sendMultipartComplete($this->test_api_key,
            $parts, $upload_data, $metadata, $this->test_security);

        $this->assertNotNull($filelink);
    }
}