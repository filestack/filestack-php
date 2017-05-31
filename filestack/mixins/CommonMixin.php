<?php
namespace Filestack\Mixins;

use Filestack\FilestackConfig;
use Filestack\Filelink;
use Filestack\FileSecurity;
use Filestack\FilestackException;

use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;

/**
 * Mixin for common functionalities used by most Filestack objects
 *
 */
trait CommonMixin
{
    protected $http_client;
    protected $http_promises;

    protected $user_agent_header;
    protected $source_header;

    /**
     * Check if a string is a valid url.
     *
     * @param   string  $url    url string to check
     *
     * @return bool
     */
    public function isUrl($url)
    {
        $path = parse_url($url, PHP_URL_PATH);
        $encoded_path = array_map('urlencode', explode('/', $path));
        $url = str_replace($path, implode('/', $encoded_path), $url);

        return filter_var($url, FILTER_VALIDATE_URL);
    }

    /**
     * Delete a file from cloud storage
     *
     * @param string            $handle         Filestack file handle to delete
     * @param string            $api_key        Filestack API Key
     * @param FilestackSecurity $security       Filestack security object is
     *                                          required for this call
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return bool (true = delete success, false = failed)
     */
    public function sendDelete($handle, $api_key, $security)
    {
        $url = sprintf('%s/file/%s?key=%s',
            FilestackConfig::API_URL, $handle, $api_key);

        if ($security) {
            $url = $security->signUrl($url);
        }

        $response = $this->sendRequest('DELETE', $url);
        $status_code = $response->getStatusCode();

        // handle response
        if ($status_code !== 200) {
            throw new FilestackException($response->getBody(), $status_code);
        }

        return true;
    }

    /**
     * Download a file to specified destination given a url
     *
     * @param string            $url            Filestack file url
     * @param string            $destination    destination filepath to save to,
     *                                          can be a directory name
     * @param FilestackSecurity $security       Filestack security object if
     *                                          security settings is turned on
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return bool (true = download success, false = failed)
     */
    protected function sendDownload($url, $destination, $security = null)
    {
        if (is_dir($destination)) {
            // destination is a folder
            $json = $this->sendGetMetaData($url, ["filename"], $security);
            $remote_filename = $json['filename'];
            $destination .= $remote_filename;
        }

        // sign url if security is passed in
        if ($security) {
            $url = $security->signUrl($url);
        }

        # send request
        $options = ['sink' => $destination];

        $url .= '&dl=true';
        $response = $this->sendRequest('GET', $url, $options);
        $status_code = $response->getStatusCode();

        // handle response
        if ($status_code !== 200) {
            throw new FilestackException($response->getBody(), $status_code);
        }

        return true;
    }

    /**
     * Get the content of a file.
     *
     * @param string            $url        Filestack file url
     * @param FilestackSecurity $security   Filestack security object if
     *                                      security settings is turned on
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return string (file content)
     */
    protected function sendGetContent($url, $security = null)
    {
        // sign url if security is passed in
        if ($security) {
            $url = $security->signUrl($url);
        }

        $response = $this->sendRequest('GET', $url);
        $status_code = $response->getStatusCode();

        // handle response
        if ($status_code !== 200) {
            throw new FilestackException($response->getBody(), $status_code);
        }

        $content = $response->getBody()->getContents();

        return $content;
    }

    /**
     * Get the metadata of a remote file.  Will only retrieve specific fields
     * if optional fields are passed in
     *
     * @param                   $url        url of file
     * @param                   $fields     optional, specific fields to retrieve.
     *                                      values are: mimetype, filename, size,
     *                                      width, height,location, path,
     *                                      container, exif, uploaded (timestamp),
     *                                      writable, cloud, source_url
     * @param FilestackSecurity $security   Filestack security object if
     *                                      security settings is turned on
     *
     * @throws FilestackException   if API call fails, e.g 400 bad request
     *
     * @return array
     */
    protected function sendGetMetaData($url, $fields = [], $security = null)
    {
        $url .= "/metadata?";
        foreach ($fields as $field_name) {
            $url .= "&$field_name=true";
        }

        // sign url if security is passed in
        if ($security) {
            $url = $security->signUrl($url);
        }

        $response = $this->sendRequest('GET', $url);
        $status_code = $response->getStatusCode();

        if ($status_code !== 200) {
            throw new FilestackException($response->getBody(), $status_code);
        }

        $json_response = json_decode($response->getBody(), true);

        return $json_response;
    }

    /**
     * Overwrite a file in cloud storage
     *
     * @param string            $resource   url or filepath
     * @param string            $handle     Filestack file handle to overwrite
     * @param string            $api_key    Filestack API Key
     * @param FilestackSecurity $security   Filestack security object is
     *                                      required for this call
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack\Filelink
     */
    public function sendOverwrite($resource, $handle, $api_key, $security)
    {
        $url = sprintf('%s/file/%s?key=%s',
            FilestackConfig::API_URL, $handle, $api_key);

        if ($security) {
            $url = $security->signUrl($url);
        }

        if ($this->isUrl($resource)) {
            // external source (passing url instead of filepath)
            $data['form_params'] = ['url' => $resource];
        }
        else {
            // local file
            $data['body'] = fopen($resource, 'r');
        }

        $response = $this->sendRequest('POST', $url, $data);
        $filelink = $this->handleResponseCreateFilelink($response);

        return $filelink;
    }

    /**
     * Trigger the start of a multipart upload
     *
     * @param string        $api_key        Filestack API Key
     * @param string        $metadata       metadata of file: filename, filesize,
     *                                      mimetype, location
     * @param FilestackSecurity $security   Filestack security object if
     *                                      security settings is turned on
     *
     * @throws FilestackException   if API call fails
     *
     * @return json
     */
    public function sendMultipartStart($api_key, $metadata, $security = null)
    {
        $data = [];
        $this->addMultipartData($data, 'apikey',         $api_key);
        $this->addMultipartData($data, 'filename',       $metadata['filename']);
        $this->addMultipartData($data, 'mimetype',       $metadata['mimetype']);
        $this->addMultipartData($data, 'size',           $metadata['filesize']);
        $this->addMultipartData($data, 'store_location', $metadata['location']);

        array_push($data, ['name' => 'files',
            'contents' => '',
            'filename' => $metadata['filename']
        ]);
        $this->multipartApplySecurity($data, $security);

        $url = FilestackConfig::UPLOAD_URL . '/multipart/start';
        $response = $this->sendRequest('POST', $url, ['multipart' => $data]);
        $json = $this->handleResponseDecodeJson($response);

        return $json;
    }

    /**
     * Upload a chunk of the file to server.
     *
     * @param array             $jobs           array of jobs to process
     * @param FilestackSecurity $security       Filestack security object if
     *                                          security settings is turned on
     *
     * @throws FilestackException   if API call fails
     *
     * @return json
     */
    public function sendMultipartJobs($jobs, $security = null)
    {
        $num_jobs = count($jobs);
        $parts_etags = [];

        $num_concurrent = FilestackConfig::UPLOAD_CONCURRENT_JOBS;
        $jobs_completed = 0;
        $start_index = 1;
        $end_index = $num_jobs < $num_concurrent ? $num_jobs : $num_concurrent;

        // loop through jobs and make async concurrent calls
        while ($jobs_completed < $num_jobs) {
            $upload_promises = [];
            $s3_promises = [];

            $this->appendUploadPromises($jobs, $start_index, $end_index,
                $upload_promises, $jobs_completed, $security);

            // settle promises (concurrent async requests to upload api)
            $api_results = \GuzzleHttp\Promise\settle($upload_promises)->wait();

            $this->appendS3Promises($jobs, $api_results, $s3_promises);

            // settle promises (concurrent async requests to s3 api)
            $s3_results = \GuzzleHttp\Promise\settle($s3_promises)->wait();
            $this->multipartGetTags($s3_results, $parts_etags);

            // clear arrays
            unset($upload_promises);
            unset($s3_promises);

            // increment jobs start and end indexes
            $start_index += $num_concurrent;
            $end_index += $num_concurrent;
            if ($end_index > $jobs_completed) {
                $end_index += $num_jobs - $jobs_completed;
            }
        } // end_while
        $parts_etags = implode(';', $parts_etags);

        return $parts_etags;
    }

    /**
     * Trigger the end of a multipart upload
     *
     * @param string            $api_key        Filestack API Key
     * @param string            $job_uri        uri of job to mark as complete
     * @param string            $region         job region
     * @param string            $upload_id      upload id of job
     * @param string            $parts          parts:etags, semicolon separated
     *                                          e.g. '1:etag_1;2:etag_2;3:etag_3'
     * @param FilestackSecurity $security       Filestack security object if
     *                                          security settings is turned on
     *
     * @throws FilestackException   if API call fails
     *
     * @return json
     */
    public function sendMultipartComplete($api_key, $parts, $upload_data,
                                          $metadata, $security = null)
    {
        $data = [];
        $this->addMultipartData($data, 'apikey', $api_key);
        $this->addMultipartData($data, 'parts',  $parts);

        $this->addMultipartData($data, 'uri',       $upload_data['uri']);
        $this->addMultipartData($data, 'region',    $upload_data['region']);
        $this->addMultipartData($data, 'upload_id', $upload_data['upload_id']);

        $this->addMultipartData($data, 'filename',       $metadata['filename']);
        $this->addMultipartData($data, 'mimetype',       $metadata['mimetype']);
        $this->addMultipartData($data, 'size',           $metadata['filesize']);
        $this->addMultipartData($data, 'store_location', $metadata['location']);

        array_push($data, ['name' => 'files',
            'contents' => '',
            'filename' => $metadata['filename']]);
        $this->multipartApplySecurity($data, $security);

        $url = FilestackConfig::UPLOAD_URL . '/multipart/complete';
        $response = $this->sendRequest('POST', $url, ['multipart' => $data]);
        $json = $this->handleResponseCreateFilelink($response);

        return $json;
    }

    /**
     * Handle a Filestack response and create a filelink object
     *
     * @param   Http\Message\Response    $response    response object
     *
     * @throws FilestackException   if statuscode is not OK
     *
     * @return Filestack\Filelink
     */
    protected function handleResponseCreateFilelink($response)
    {
        $status_code = $response->getStatusCode();

        if ($status_code !== 200) {
            throw new FilestackException($response->getBody(), $status_code);
        }

        $json_response = json_decode($response->getBody(), true);
        $url = $json_response['url'];
        $file_handle = substr($url, strrpos($url, '/') + 1);

        $filelink = new Filelink($file_handle, $this->api_key, $this->security);
        $filelink->metadata['filename'] = $json_response['filename'];
        $filelink->metadata['size'] = $json_response['size'];
        $filelink->metadata['mimetype'] = 'unknown';

        if (isset($json_response['type'])) {
            $filelink->metadata['mimetype'] = $json_response['type'];
        } elseif (isset($json_response['mimetype'])) {
            $filelink->metadata['mimetype'] = $json_response['mimetype'];
        }

        return $filelink;
    }

    protected function handleResponseDecodeJson($response)
    {
        $status_code = $response->getStatusCode();
        if ($status_code !== 200) {
            throw new FilestackException($response->getBody(), $status_code);
        }

        $json_response = json_decode($response->getBody(), true);
        return $json_response;
    }

    /**
     * Send request
     *
     * @param string    $url        url to post to
     * @param array     $params     optional params to send
     * @param array     $headers    optional headers to send
     */
    protected function sendRequest($method, $url, $data = [], $headers = [])
    {
        $this->addRequestSourceHeader($headers);
        $data['http_errors'] = false;
        $data['headers'] = $headers;

        $response = $this->http_client->request($method, $url, $data);
        return $response;
    }

    /**
     * Get source header
     */
    protected function getSourceHeaders()
    {
        $headers = [];

        if (!$this->user_agent_header || !$this->source_header) {
            $version = trim(file_get_contents(__DIR__ . '/../../VERSION'));

            if (!$this->user_agent_header) {
                $this->user_agent_header = sprintf('filestack-php-%s',
                    $version);
            }

            if (!$this->source_header) {
                $this->source_header = sprintf('PHP-%s',
                    $version);
            }
        }

        $headers['user-agent'] = $this->user_agent_header;
        $headers['filestack-source'] = $this->source_header;

        //user_agent_header
        return $headers;
    }

    /**
     * Get a chunk from a file given starting seek point.
     */
    protected function multipartGetChunk($filepath, $seek_point) {
        $handle = fopen($filepath, 'r');
        fseek($handle, $seek_point);
        $chunk = fread($handle, FilestackConfig::UPLOAD_CHUNK_SIZE);
        fclose($handle);
        $handle = null;

        return $chunk;
    }

    /**
     * Append source header to request headers array
     */
    protected function addRequestSourceHeader(&$headers)
    {
        $source_headers = $this->getSourceHeaders();
        $headers['User-Agent'] = $source_headers['user-agent'];
        $headers['Filestack-Source'] = $source_headers['filestack-source'];
    }

    /**
     * Add a multipart data item
     */
    protected function addMultipartData(&$data, $name, $value)
    {
        array_push($data, ['name' => $name, 'contents' => $value]);
    }

    /**
     * Create data multipart data for multipart upload api request
     */
    protected function buildMultipartJobData($job)
    {
        $data = [];
        $this->addMultipartData($data, 'apikey',            $job['api_key']);
        $this->addMultipartData($data, 'md5',               $job['md5']);
        $this->addMultipartData($data, 'size',              $job['chunksize']);
        $this->addMultipartData($data, 'region',            $job['region']);
        $this->addMultipartData($data, 'upload_id',         $job['upload_id']);
        $this->addMultipartData($data, 'uri',               $job['uri']);
        $this->addMultipartData($data, 'part',              $job['part_num']);
        $this->addMultipartData($data, 'store_location',    $job['location']);

        array_push($data, [
            'name'      => 'files',
            'contents'  => '',
            'filename'  => $job['filename']]);

        return $data;
    }

    /**
     * append promises for multipart async concurrent calls
     */
    protected function appendUploadPromises($jobs, $start_index, $end_index,
        &$upload_promises, &$jobs_completed, $security)
    {
        $num_jobs = count($jobs);
        $headers = [];
        $this->addRequestSourceHeader($headers);

        // loop from current start of concurrent jobs to end of of concurrent jobs
        for ($i=$start_index; $i <= $end_index; $i++) {
            if ($i > $num_jobs) {
                break;
            }

            $job = $jobs[$i];
            $data = $this->buildMultipartJobData($job);
            $this->multipartApplySecurity($data, $security);

            // build promises to execute concurrent POST requests to upload api
            $upload_promises[] = $this->http_client->requestAsync('POST',
                FilestackConfig::UPLOAD_URL . '/multipart/upload', [
                'headers' => $headers,
                'multipart' => $data
            ]);
            $jobs_completed++;
        }
    }

    /**
     * append promises for multipart async concurrent calls
     */
    protected function appendS3Promises($jobs, $api_results, &$s3_promises)
    {
        foreach ($api_results as $result) {
            if (isset($result['value'])) {
                $json = json_decode($result['value']->getBody(), true);
                $query = parse_url($json['url'], PHP_URL_QUERY);
                parse_str($query, $params);
                $part_num = intval($params['partNumber']);

                $headers = $json['headers'];
                $seek_point = $jobs[$part_num]['seek_point'];
                $filepath = $jobs[$part_num]['filepath'];

                $chunk = $this->multipartGetChunk($filepath, $seek_point);

                // build promises to execute concurrent PUT requests to s3
                $s3_promises[$part_num] = $this->http_client->requestAsync('PUT',
                    $json['url'],
                    [
                        'body' => $chunk,
                        'headers' => $headers
                    ]
                );
            }
        }
    }

    /**
     * apppend security policy and signature to request data if security is on
     */
    protected function multipartApplySecurity(&$data, $security)
    {
        if ($security) {
            $this->addMultipartData($data, 'policy',    $security->policy);
            $this->addMultipartData($data, 'signature', $security->signature);
        }
    }

    /**
     * Parse results of s3 calls and append to parts_etags array
     */
    protected function multipartGetTags($s3_results, &$parts_etags)
    {
        foreach ($s3_results as $part_num => $result) {
            try {
                $etag = $result['value']->getHeader('ETag')[0];
                $part_etag = sprintf('%s:%s', $part_num, $etag);
                array_push($parts_etags, $part_etag);
            } catch (\Exception $e) {
                throw new FilestackException(
                    "Error encountered getting eTags: " . $e->getMessage(),
                    500);
            }
        }
    }
}
