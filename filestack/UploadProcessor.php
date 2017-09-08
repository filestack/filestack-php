<?php
namespace Filestack;

use Filestack\FilestackConfig;
use Filestack\HttpStatusCodes;

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * Object used by the Filestack client to process an
 * upload task.
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
    public function __construct($api_key, $security = null,
        $http_client = null, $intelligent = false)
    {
        $this->api_key = $api_key;
        $this->security = $security;
        $this->intelligent = $intelligent;

        if (is_null($http_client)) {
            $http_client = new Client();
        }
        $this->http_client = $http_client; // CommonMixin
    }

    public function intelligenceEnabled($upload_data) {
        return (array_key_exists('upload_type', $upload_data) &&
        $upload_data['upload_type'] == 'intelligent_ingestion');
    }

    public function setIntelligent($intelligent)
    {
        $this->intelligent = $intelligent;
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
     * @return array['statuscode', 'json']
     */
    public function run($api_key, $metadata, $upload_data)
    {
        $parts = $this->createParts($api_key, $metadata, $upload_data);

        // upload parts
        $result = $this->processParts($parts);

        // parts uploaded, register complete and wait for acceptance
        $wait_attempts = FilestackConfig::UPLOAD_WAIT_ATTEMPTS;
        $wait_time = FilestackConfig::UPLOAD_WAIT_SECONDS;
        $accepted_code = HttpStatusCodes::HTTP_ACCEPTED;

        $completed_status_code = $accepted_code;
        $completed_result = ['status_code' => 0, 'filelink' => []];

        while ($completed_status_code == $accepted_code &&
          $wait_attempts > 0) {

            $completed_result = $this->registerComplete($api_key, $result,
                          $upload_data, $metadata);

            $completed_status_code = $completed_result['status_code'];
            if ($completed_status_code == $accepted_code) {
              sleep($wait_time);
            }
            $wait_attempts--;
        }

        return $completed_result;
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
            $chunk_offset = 0;
            $chunks = [];

            if ($this->intelligent) {

                // split part into chunks
                $num_chunks = 1;
                $chunk_size = $metadata['filesize'];

                if ($metadata['filesize'] > $max_chunk_size) {
                    $num_chunks = ceil($max_part_size / $max_chunk_size);
                    $chunk_size = $max_chunk_size;
                }

                while ($num_chunks > 0) {
                    array_push($chunks, [
                        'offset'        => $chunk_offset,
                        'seek_point'    => $seek_point,
                        'size'          => $chunk_size,
                    ]);

                    $chunk_offset += $max_chunk_size;
                    $seek_point += $max_chunk_size;
                    if ($seek_point >= $metadata['filesize']) {
                        break;
                    }

                    $num_chunks--;
                }
            }
            else {
                // 1 part = 1 chunk
                array_push($chunks, [
                    'offset'        => 0,
                    'seek_point'    => $seek_point,
                    'size'          => $max_part_size
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
        $num_parts = count($parts);
        $parts_etags = [];
        $parts_completed = 0;

        $max_retries = FilestackConfig::MAX_RETRIES;

        $current_part_index = 0;
        while($parts_completed < $num_parts) {
            $part = $parts[$current_part_index];
            $part['part_size'] = 0;
            $chunks = $part['chunks'];

            // process chunks of current part
            $promises = $this->processChunks($part, $chunks);

            // sends s3 chunks asyncronously
            $s3_results = $this->settlePromises($promises);
            $this->handleS3PromisesResult($s3_results);

            // handle fulfilled promises
            if ($this->intelligent) {
                // commit part
                $this->commitPart($part);
            }
            else {
                $part_num = array_key_exists('part_num', $part) ?
                    $part['part_num'] : 1;

                $this->multipartGetTags($part_num, $s3_results, $parts_etags);
            }

            unset($promises);
            unset($s3_results);

            $current_part_index++;
            $parts_completed++;
        }

        if (!$this->intelligent) {
            return implode(';', $parts_etags);
        }

        return $parts_completed;
    }

    /**
     * Process the chunks of a part the file to server.
     *
     * @param object    $part           the part to process
     * @param array     $chunks         the chunks of part to process
     *
     * @throws FilestackException   if API call fails
     *
     * @return Promises to send Asyncronously to s3
     */
    protected function processChunks($part, $chunks)
    {
        $upload_url = FilestackConfig::UPLOAD_URL . '/multipart/upload';
        $max_retries = FilestackConfig::MAX_RETRIES;

        $num_retries = 0;
        $promises = [];

        if (!array_key_exists('part_size', $part)) {
            $part['part_size'] = 0;
        }

        for($i=0; $i<count($chunks); $i++) {
            $current_chunk = $chunks[$i];
            $seek_point = $current_chunk['seek_point'];

            $chunk_content = $this->getChunkContent($part['filepath'], $seek_point,
                $current_chunk['size']);

            $current_chunk['md5'] = trim(base64_encode(md5($chunk_content, true)));
            $current_chunk['size'] = strlen($chunk_content);
            $part['part_size'] += $current_chunk['size'];

            $data = $this->buildChunkData($part, $current_chunk);
            $response = $this->sendRequest('POST', $upload_url, ['multipart' => $data]);

            try {
                $json = $this->handleResponseDecodeJson($response);
                $url = $json['url'];
                $headers = $json['headers'];

                $this->appendPromise($promises, 'PUT', $url, [
                    'body' => $chunk_content,
                    'headers' => $headers
                ]);
            }
            catch(FilestackException $e) {
                $status_code = $e->getCode();
                if ($this->intelligent && $num_retries < $max_retries) {
                    $num_retries++;
                    if (HttpStatusCodes::isServerError($status_code)) {
                        $wait_time = $this->get_retry_miliseconds($num_retries);
                        usleep($wait_time * 1000);
                    }

                    if (HttpStatusCodes::isNetworkError($status_code) ||
                        HttpStatusCodes::isServerError($status_code)) {
                        // reset index to retry this iteration
                        $i--;
                    }
                    continue;
                }

                throw new FilestackException($e->getMessage(), $status_code);
            }
        }

        return $promises;
    }

    /**
     * All chunks of this part has been uploaded.  We have to call commit to
     * let the uploader API knows.
     *
     * @param object    $part           the part to process
     *
     * @throws FilestackException   if API call fails
     *
     * @return int status_code
     */
    protected function commitPart($part)
    {
        $commit_url = FilestackConfig::UPLOAD_URL . '/multipart/commit';
        $commit_data = $this->buildCommitData($part);

        $response = $this->sendRequest('POST', $commit_url,
                                       ['multipart' => $commit_data]);

        $status_code = $response->getStatusCode();
        if ($status_code !== 200) {
            throw new FilestackException($response->getBody(),
                $status_code);
        }
        return $status_code;
    }

    /**
     * Upload a chunk of data to S3
     * @param string    $url        the S3 URL (from the register task call)
     * @param array     $headers    auth headers from the register task call
     * @param binary    $chunk      chunk of data to upload
     *
     * @throws FilestackException   if API call fails
     *
     * @return int status_code
     */
    protected function uploadChunkToS3($url, $headers, $chunk)
    {
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $params);

        $part_num = 1;
        if (array_key_exists('partNumber', $params)) {
            $part_num = intval($params['partNumber']);
        }

        $response = $this->http_client->request('PUT',
            $url,
            [
                'body' => $chunk,
                'headers' => $headers
            ]
        );

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
        $this->appendData($data, 'parts',           $parts_etags);

        if ($this->intelligent) {
            $this->appendData($data, 'multipart', true);
        }

        array_push($data, ['name' => 'files',
            'contents' => '',
            'filename' => $metadata['filename']
        ]);

        $this->appendSecurity($data);

        $url = FilestackConfig::UPLOAD_URL . '/multipart/complete';
        $response = $this->sendRequest('POST', $url, ['multipart' => $data]);
        $status_code = $response->getStatusCode();

        $filelink = null;
        if ($status_code == 200) {
            $filelink = $this->handleResponseCreateFilelink($response);
        }

        return ['status_code' => $status_code, 'filelink' => $filelink];
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
            $this->appendData($data, 'offset', $chunk_data['offset']);
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
        $this->appendData($data, 'store_location',          $part['location']);
        $this->appendData($data, 'region',            $part['region']);
        $this->appendData($data, 'upload_id',         $part['upload_id']);
        $this->appendData($data, 'part',              $part['part_num']);
        $this->appendData($data, 'size',              $part['filesize']);

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

    /**
     * Append security params
     */
    protected function appendSecurity(&$data)
    {
        if ($this->security) {
            $this->appendData($data, 'policy',    $this->security->policy);
            $this->appendData($data, 'signature', $this->security->signature);
        }
    }

    /**
     * Parse results of s3 calls and append to parts_etags array
     */
    protected function multipartGetTags($part_num, $s3_results, &$parts_etags)
    {
        foreach ($s3_results as $result) {
            if (isset($result['value']) && $result['value']) {
                $etag = $result['value']->getHeader('ETag')[0];
                $part_etag = sprintf('%s:%s', $part_num, $etag);
                array_push($parts_etags, $part_etag);
            }
        }
    }

    /**
     * Handle results of promises after async calls
     */
    protected function handleS3PromisesResult($s3_results)
    {
        foreach ($s3_results as $promise) {
            if ($promise['state'] !== 'fulfilled') {
                $code = HttpStatusCodes::HTTP_SERVICE_UNAVAILABLE;
                if (array_key_exists('value', $promise)) {
                    $response = $promise['value'];
                    $code = $response->getStatusCode();
                }
                throw new FilestackException("Errored uploading to s3", $code);
            }
        }
    }
}