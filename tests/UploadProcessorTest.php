<?php
namespace Filestack\Tests;

use Filestack\FilestackConfig;
use Filestack\UploadProcessor;
use Filestack\FilestackException;

class UploadProcessorTest extends BaseTest
{
    private $file_metadata;
    private $upload_data;

    public function __construct()
    {
        $this->test_filepath = __DIR__ . '/testfiles/landscape_24mb_img.jpg';
        $this->file_metadata = [
            'filepath' => $this->test_filepath,
            'filename' => basename($this->test_filepath),
            'filesize' => filesize($this->test_filepath),
            'mimetype' => 'text',
            'location' => 's3'
        ];

        $this->upload_data = [
            'uri'       => 'http://someuri',
            'region'    => 'some-region',
            'upload_id' => 1234
        ];
    }

    /**
     * Test initializing UploadProcessor with an API Key
     */
    public function testUploadProcessorInitialized()
    {
        $upload_processor = new UploadProcessor(
            $this->test_api_key,
            $this->test_security
        );
        $this->assertEquals($upload_processor->api_key, $this->test_api_key);
    }

    /**
     * Test register upload task
     */
    public function testRegisterUploadTaskSuccess()
    {
        $expected_upload_id = 'some_upload_id';
        $mock_response = new MockHttpResponse(
            200,
            '{"upload_id": "' . $expected_upload_id . '"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $upload_processor = new MockUploadProcessor(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $json = $upload_processor->registerUploadTask($this->test_api_key,
            $this->file_metadata);

        $this->assertEquals($expected_upload_id, $json['upload_id']);
    }

    /**
     * Test register upload task throws exception
     */
    public function testRegisterUploadTaskException()
    {
        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $mock_response = new MockHttpResponse(
            400,
            'bad data format'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $upload_processor = new MockUploadProcessor(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $upload_processor->registerUploadTask($this->test_api_key,
            $this->file_metadata);
    }

    /**
     * Test create parts
     */
    public function testCreatePartsSuccess()
    {
        $expected_upload_id = 'some_upload_id';
        $mock_response = new MockHttpResponse(
            200,
            '{"upload_id": "' . $expected_upload_id . '"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $upload_processor = new MockUploadProcessor(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $parts = $upload_processor->callCreateParts($this->test_api_key,
            $this->file_metadata, $this->upload_data);

        $max_part_size = FilestackConfig::UPLOAD_PART_SIZE;

        $expected_num_parts = ceil($this->file_metadata['filesize']
                                   / $max_part_size);
        $expected_num_chunks = 1;

        $this->assertEquals($expected_num_parts, count($parts));
        $this->assertEquals($expected_num_chunks, count($parts[0]['chunks']));
    }

    /**
     * Test create parts for intelligent ingestion flow
     */
    public function testCreatePartsIntelligentSuccess()
    {
        $mock_response = new MockHttpResponse(200,
            json_encode([
                'filename'  => 'somefilename.jpg',
                'size'      => '1000',
                'type'      => 'image/jpg',
                'url'       => 'https://cdn.filestack.com/handle?partNum=1',
                'uri'       => 'https://uploaduri/handle?partNum=1',
                'region'    => 'us-east-1',
                'upload_id' => 'test-upload-id'
            ])
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $upload_processor = new MockUploadProcessor(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );
        $upload_processor->setIntelligent(true);

        $parts = $upload_processor->callCreateParts($this->test_api_key,
            $this->file_metadata, $this->upload_data);

        $max_part_size = FilestackConfig::UPLOAD_PART_SIZE;
        $max_chunk_size = FilestackConfig::UPLOAD_CHUNK_SIZE;

        $expected_num_parts = ceil($this->file_metadata['filesize']
                                    / $max_part_size);
        $expected_num_chunks = ceil($max_part_size / $max_chunk_size);

        $this->assertEquals($expected_num_parts, count($parts));
        $this->assertEquals($expected_num_chunks, count($parts[0]['chunks']));
    }

    /**
     * Test process parts
     */
    public function testProcessPartsSuccess()
    {
        $test_headers = ['ETag' =>['some-etag']];
        $mock_response = new MockHttpResponse(200,
            json_encode([
                'filename'  => 'somefilename.jpg',
                'size'      => '1000',
                'type'      => 'image/jpg',
                'url'       => 'https://cdn.filestack.com/filehandle?partNum=1',
                'uri'       => 'https://uploaduri/handle?partNum=1',
                'region'    => 'us-east-1',
                'upload_id' => 'test-upload-id',
                'headers'    => []
            ]),
            $test_headers
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $upload_processor = new MockUploadProcessor(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $parts = $upload_processor->callCreateParts($this->test_api_key,
            $this->file_metadata, $this->upload_data);

        $result = $upload_processor->callProcessParts($parts);

        $this->assertNotNull($result);
        $this->assertNotFalse($result);
    }

    /**
     * Test process parts
     */
    public function testProcessPartsIntelligentSuccess()
    {
        $test_headers = ['ETag' =>['some-etag']];
        $mock_response = new MockHttpResponse(200,
            json_encode([
                'filename'  => 'somefilename.jpg',
                'size'      => '1000',
                'type'      => 'image/jpg',
                'url'       => 'https://cdn.filestack.com/filehandle?partNum=1',
                'uri'       => 'https://uploaduri/handle?partNum=1',
                'region'    => 'us-east-1',
                'upload_id' => 'test-upload-id',
                'headers'    => []
            ]),
            $test_headers
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $upload_processor = new MockUploadProcessor(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );
        $upload_processor->setIntelligent(true);

        $parts = $upload_processor->callCreateParts($this->test_api_key,
            $this->file_metadata, $this->upload_data);

        $result = $upload_processor->callProcessParts($parts);

        $this->assertNotNull($result);
        $this->assertNotFalse($result);
    }

    /**
     * Test process parts
     */
    public function testProcessPartsException()
    {
        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $mock_response = new MockHttpResponse(
            400,
            'bad data format'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $upload_processor = new MockUploadProcessor(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $parts = $upload_processor->callCreateParts($this->test_api_key,
            $this->file_metadata, $this->upload_data);

        $upload_processor->callProcessParts($parts);
    }

    public function testProcessChunks()
    {
        $mock_response1 = new MockHttpResponse(408,  '{"error": "timed out"}');
        $mock_response2 = new MockHttpResponse(504,  '{"error": "Gateway timed out"}');
        $mock_response3 = new MockHttpResponse(200,
            json_encode([
                'url'       => 'https://some-s3-url/somedata',
                'part_size' => 1024,
                'headers'   => []
            ])
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturnOnConsecutiveCalls($mock_response1, $mock_response2,
                $mock_response3,
                $mock_response3,
                $mock_response3,
                $mock_response3,
                $mock_response3,
                $mock_response3,
                $mock_response3,
                $mock_response3);

        $upload_processor = new MockUploadProcessor(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );
        $upload_processor->setIntelligent(true);

        $parts = $upload_processor->callCreateParts($this->test_api_key,
            $this->file_metadata, $this->upload_data);

        $part = $parts[0];
        $chunks = $part['chunks'];

        $promises = $upload_processor->callProcessChunks($part, $chunks);

        $this->assertTrue(count($promises) > 0);
    }

    /**
     * Test handling promises from s3
     */
    public function testUnFulfilledPromises()
    {
        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(500);

        $mock_s3_response = new MockHttpResponse(
            500,
            new MockHttpResponseBody('{"error": "some-error"}')
        );

        $s3_results = [
            [
                'state' => 'fulfilled',
                'value' => $mock_s3_response
            ],
            [
                'state' => 'rejected',
                'value' => $mock_s3_response
            ]
        ];

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $upload_processor = new MockUploadProcessor(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $parts = $upload_processor->callHandleS3PromisesResult($s3_results);
    }

    /**
     * Test commitPart
     */
    public function testCommitPartException()
    {
        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $mock_response = new MockHttpResponse(
            400,
            'bad data format'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $upload_processor = new MockUploadProcessor(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $part = [
            'api_key'       => $this->test_api_key,
            'uri'           => 'http://someuri',
            'location'      => 's3',
            'region'        => 'some-region',
            'upload_id'     => 'some-upload-id',
            'part_num'      => 1,
            'filesize'      => 1024,
            'filename'      => 'somefilename',
        ];
        $upload_processor->callCommitPart($part);
    }

    /**
     * Test upload chunk to s3
     */
    public function testUploadChunkToS3()
    {
        $mock_response = new MockHttpResponse(200,
            json_encode([
                'some-field' => 'some-value'
            ])
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $upload_processor = new MockUploadProcessor(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $url = 'http://s3-url?partNumber=1';
        $headers = ['Auth' => 'x-some-auth-val'];
        $chunk = "test-content";
        $result = $upload_processor->callUploadChunkToS3($url, $headers, $chunk);

        $this->assertNotNull($result);
    }

    /**
     * Test upload chunk to s3
     */
    public function testUploadChunkToS3Exception()
    {
        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $mock_response = new MockHttpResponse(
            400,
            'bad data format'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $upload_processor = new MockUploadProcessor(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $url = 'http://s3-url?partNumber=1';
        $headers = ['Auth' => 'x-some-auth-val'];
        $chunk = "test-content";
        $upload_processor->callUploadChunkToS3($url, $headers, $chunk);
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

        $s3_results = [
            ['value' => $mock_response]
        ];

        $upload_processor = new MockUploadProcessor(
            $this->test_api_key,
            $this->test_security
        );

        $parts_etags = [];
        $upload_processor->callMultipartGetTags(1, $s3_results, $parts_etags);
        $this->assertNotEmpty($parts_etags);
    }

    /**
     * Test register completed upload
     */
    public function testRegisterComplete()
    {
        $mock_response1 = new MockHttpResponse(202,  '{accepted: true}');
        $mock_response2 = new MockHttpResponse(200,
            json_encode([
                'filename'  => 'somefilename.jpg',
                'size'      => '1000',
                'type'      => 'image/jpg',
                'url'       => 'https://cdn.filestack.com/filehandle?partNum=1',
                'uri'       => 'https://uploaduri/handle?partNum=1',
                'region'    => 'us-east-1',
                'upload_id' => 'test-upload-id',
                'headers'    => []
            ])
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
            ->willReturnOnConsecutiveCalls($mock_response1, $mock_response2);

        $upload_processor = new MockUploadProcessor(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $result = $upload_processor->callRegisterComplete($this->test_api_key,
            "eTags", $this->upload_data, $this->file_metadata);

        $this->assertNotNull($result);
    }
}
