<?php
namespace Filestack;

use Filestack\FilestackConfig;

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * FilestackClient client.  This is the main object to
 * make functional calls to the Filestack API.
 */
class UploadProcessor
{
    use Mixins\CommonMixin;

    public $api_key;
    protected $security;
    protected $intelligent;

    /**
     * UploadProcessor constructor
     *
     * @param string            $api_key        your Filestack API Key
     * @param FilestackSecurity $security       Filestack security object if
     *                                          security settings is turned on
     * @param GuzzleHttp\Client $http_client    DI http client, will instantiate
     *                                          one if not passed in
     */
    public function __construct($api_key, $security = null, $http_client = null)
    {
        $this->api_key = $api_key;
        $this->security = $security;
        $this->intelligent = false;

        if (is_null($http_client)) {
            $http_client = new Client();
        }
        $this->http_client = $http_client; // CommonMixin
    }

    /**
     * Trigger the start of an upload task
     *
     * @param string        $api_key        Filestack API Key
     * @param string        $metadata       metadata of file: filename, filesize,
     *                                      mimetype, location
     *
     * @throws FilestackException   if API call fails
     *
     * @return json
     */
    public function registerUploadTask($api_key, $metadata)
    {
        $data = [];
        $this->appendData($data, 'apikey',         $api_key);
        $this->appendData($data, 'filename',       $metadata['filename']);
        $this->appendData($data, 'mimetype',       $metadata['mimetype']);
        $this->appendData($data, 'size',           $metadata['filesize']);
        $this->appendData($data, 'store_location', $metadata['location']);
        $this->appendData($data, 'multipart',      true);

        array_push($data, ['name' => 'files',
            'contents' => '',
            'filename' => $metadata['filename']
        ]);

        $this->appendSecurity($data);

        $url = FilestackConfig::UPLOAD_URL . '/multipart/start';
        $response = $this->sendRequest('POST', $url, ['multipart' => $data]);
        $json = $this->handleResponseDecodeJson($response);

        return $json;
    }

    /**
     * Run upload process, including splitting up the file and sending parts
     * concurrently in chunks.
     *
     * @param string        $api_key        Filestack API Key
     * @param string        $metadata       metadata of file: filename, filesize,
     *                                      mimetype, location
     * @param array         $upload_data    filestack upload data from register
     *                                      call: uri, region, upload_id
     *
     * @throws FilestackException   if API call fails
     *
     * @return json
     */
    public function run($api_key, $metadata, $upload_data)
    {
        $parts = $this->createParts($api_key, $metadata, $upload_data);

        // upload parts
        $result = $this->processParts($parts);

        // mark upload as completed
        $success = $this->registerComplete($api_key, $result,
                          $upload_data, $metadata);

        return $success;
    }

    /**
     * Take a file and separate it into parts, creating an array of parts to
     * process.
     *
     * @param string    $api_key        Filestack API Key
     * @param array     $metadata       Metadata of file: filename, filesize,
     *                                  mimetype, location
     * @param array     $upload_data    filestack upload data from register
     *                                  call: uri, region, upload_id
     *
     * @return Filestack/Filelink or file content
     */
    protected function createParts($api_key, $metadata, $upload_data)
    {
        $parts = [];
        $max_part_size = FilestackConfig::UPLOAD_PART_SIZE;
        $max_chunk_size = FilestackConfig::UPLOAD_CHUNK_SIZE;

        $num_parts = ceil($metadata['filesize'] / $max_part_size);
        $seek_point = 0;

        // create each part of file
        for ($i=0; $i<$num_parts; $i++) {

            // create chunks of file
            $num_chunks = 1;
            $chunk_offset = 0;
            $chunks = [];

            if ($this->intelligent &&
                    ($metadata['filesize'] > $max_chunk_size)) {

                // split part into chunks
                $num_chunks = ceil($max_part_size / $max_chunk_size);
                while ($num_chunks > 0) {
                    array_push($chunks, [
                        'offset'        => $chunk_offset,
                        'seek_point'    => $seek_point
                    ]);

                    $chunk_offset += $max_chunk_size;
                    $seek_point += $max_chunk_size;
                    $num_chunks--;
                }
            }
            else {
                // 1 part = 1 chunk
                array_push($chunks, [
                    'offset'        => 0,
                    'seek_point'    => $seek_point
                ]);
                $seek_point += $max_part_size;
            }

            array_push($parts, [
                'api_key'       => $api_key,
                'part_num'      => $i + 1,
                'uri'           => $upload_data['uri'],
                'region'        => $upload_data['region'],
                'upload_id'     => $upload_data['upload_id'],
                'filepath'      => $metadata['filepath'],
                'filename'      => $metadata['filename'],
                'filesize'      => $metadata['filesize'],
                'mimetype'      => $metadata['mimetype'],
                'location'      => $metadata['location'],
                'num_chunks'    => $num_chunks,
                'chunks'        => $chunks
            ]);
        }

        return $parts;
    }

    /**
     * Process the parts of the file to server.
     *
     * @param array             $parts          array of parts to process
     *
     * @throws FilestackException   if API call fails
     *
     * @return json
     */
    protected function processParts($parts)
    {
        $chunk_max_size = FilestackConfig::UPLOAD_CHUNK_SIZE;
        $upload_url = FilestackConfig::UPLOAD_URL . '/multipart/upload';
        $commit_url = FilestackConfig::UPLOAD_URL . '/multipart/commit';

        $num_parts = count($parts);
        $parts_etags = [];
        $parts_completed = 0;

        while($parts_completed < $num_parts) {
            $part = array_shift($parts);
            $part['part_size'] = 0;
            $chunks = $part['chunks'];

            for($i=0; $i<count($chunks); $i++) {
                $current_chunk = $chunks[$i];
                $seek_point = $current_chunk['seek_point'];

                $chunk_content = $this->getChunkContent($part['filepath'], $seek_point,
                    $chunk_max_size);

                $chunk_data['md5'] = trim(base64_encode(md5($chunk_content, true)));
                $chunk_data['size'] = strlen($chunk_content);
                $part['part_size'] += $chunk_data['size'];

                $data = $this->buildChunkData($part, $chunk_data);

                $response = $this->sendRequest('POST', $upload_url, ['multipart' => $data]);
                $json = $this->handleResponseDecodeJson($response);

                $url = $json['url'];
                $headers = $json['headers'];
                $s3_response = $this->uploadChunkToS3($url, $headers, $part, $chunk_content);

                if (!$this->intelligent) {
                    $etag = $s3_response->getHeader('ETag')[0];
                    $part_etag = sprintf('%s:%s', $part['part_num'], $etag);
                    array_push($parts_etags, $part_etag);
                }
            }

            if ($this->intelligent) {
                // commit part
                $commit_data = $this->buildCommitData($part);
                $response = $this->sendRequest('POST', $commit_url,
                                               ['multipart' => $commit_data]);

                $status_code = $response->getStatusCode();
                if ($status_code !== 200) {
                    throw new FilestackException($response->getBody(),
                        $status_code);
                }
            }

            $parts_completed++;
        }

        if (!$this->intelligent) {
            return implode(';', $parts_etags);
        }

        return true;
    }

    protected function uploadChunkToS3($url, $headers, $part, $chunk)
    {
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $params);

        $part_num = 1;
        if (array_key_exists('partNumber', $params)) {
            $part_num = intval($params['partNumber']);
        }

        $response = $this->sendRequest('PUT', $url, ['body' => $chunk], $headers);
        $status_code = $response->getStatusCode();
        if ($status_code !== 200) {
            throw new FilestackException($response->getBody(),
                $status_code);
        }

        return $response;
    }

    /**
     * Trigger the end of an upload task
     *
     * @param string            $api_key        Filestack API Key
     * @param string            $parts_etags    parts:etags, semicolon separated
     *                                          e.g. '1:etag_1;2:etag_2;3:etag_3
     * @param array             $upload_data    upload data from register
     *                                          call: uri, region, upload_id
     * @param string            $metadata       metadata of file: filename,
     *                                          filesize, mimetype, location
     *
     * @throws FilestackException   if API call fails
     *
     * @return json
     */
    protected function registerComplete($api_key, $parts_etags, $upload_data,
                                          $metadata)
    {
        $data = [];
        $this->appendData($data, 'apikey',          $api_key);
        $this->appendData($data, 'uri',             $upload_data['uri']);
        $this->appendData($data, 'region',          $upload_data['region']);
        $this->appendData($data, 'upload_id',       $upload_data['upload_id']);

        $this->appendData($data, 'size',            $metadata['filesize']);
        $this->appendData($data, 'filename',        $metadata['filename']);
        $this->appendData($data, 'mimetype',        $metadata['mimetype']);
        $this->appendData($data, 'store_location',  $metadata['location']);

        if ($this->intelligent) {
            $this->appendData($data, 'multipart', true);
        }
        else {
            $this->appendData($data, 'parts', $parts_etags);
        }

        array_push($data, ['name' => 'files',
            'contents' => '',
            'filename' => $metadata['filename']
        ]);

        $this->appendSecurity($data);

        $url = FilestackConfig::UPLOAD_URL . '/multipart/complete';
        $response = $this->sendRequest('POST', $url, ['multipart' => $data]);
        $json = $this->handleResponseCreateFilelink($response);

        return $json;
    }

    /**
     * Create data multipart data for multipart upload api request
     */
    protected function buildChunkData($part, $chunk_data)
    {
        $data = [];
        $this->appendData($data, 'apikey',            $part['api_key']);
        $this->appendData($data, 'uri',               $part['uri']);
        $this->appendData($data, 'region',            $part['region']);
        $this->appendData($data, 'upload_id',         $part['upload_id']);

        $this->appendData($data, 'part',              $part['part_num']);
        $this->appendData($data, 'store_location',    $part['location']);
        $this->appendData($data, 'md5',               $chunk_data['md5']);
        $this->appendData($data, 'size',              $chunk_data['size']);

        if ($this->intelligent) {
            $this->appendData($data, 'multipart', true);
            $this->appendData($data, 'offset', $chunk['offset']);
        }

        $this->appendSecurity($data);

        array_push($data, [
            'name'      => 'files',
            'contents'  => '',
            'filename'  => $part['filename']]);

        return $data;
    }

    protected function buildCommitData($part)
    {
        $data = [];
        $this->appendData($data, 'apikey',            $part['api_key']);
        $this->appendData($data, 'uri',               $part['uri']);
        $this->appendData($data, 'region',            $part['region']);
        $this->appendData($data, 'upload_id',         $part['upload_id']);
        $this->appendData($data, 'part',              $part['part_num']);
        $this->appendData($data, 'size',              $part['part_size']);

        array_push($data, [
            'name'      => 'files',
            'contents'  => '',
            'filename'  => $part['filename']]);

        $this->appendSecurity($data);

        return $data;
    }

    /**
     * Get a chunk from a file given starting seek point.
     */
    protected function getChunkContent($filepath, $seek_point, $chunk_size) {
        $handle = fopen($filepath, 'r');
        fseek($handle, $seek_point);
        $chunk = fread($handle, $chunk_size);
        fclose($handle);
        $handle = null;

        return $chunk;
    }

    protected function appendSecurity(&$data)
    {
        if ($this->security) {
            $this->appendData($data, 'policy',    $this->security->policy);
            $this->appendData($data, 'signature', $this->security->signature);
        }
    }
}