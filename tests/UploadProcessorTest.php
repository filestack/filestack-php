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
        $this->test_filepath = __DIR__ . '/testfiles/38mb_file.txt';
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
        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $upload_processor = new UploadProcessor(
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
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

        $result = $upload_processor->callProcessParts($parts);
    }
}
