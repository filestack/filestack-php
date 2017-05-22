<?php
namespace Filestack\Mixins;

use Filestack\FilestackConfig;
use Filestack\Filelink;
use Filestack\FileSecurity;
use Filestack\FilestackException;

/**
 * Mixin for common functionalities used by most Filestack objects
 *
 */
trait CommonMixin
{
    protected $http_client;
    protected $user_agent_header;

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
        $url = sprintf('%s/file/%s?key=%s', FilestackConfig::API_URL, $handle, $api_key);

        if ($security) {
            $url = $security->signUrl($url);
        }

        $response = $this->requestDelete($url);
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
            $json_response = $this->sendGetMetaData($url, ["filename"], $security);
            $remote_filename = $json_response['filename'];
            $destination .= $remote_filename;
        }

        // sign url if security is passed in
        if ($security) {
            $url = $security->signUrl($url);
        }

        # send request
        $headers = [];
        $options = ['sink' => $destination];

        $response = $this->requestGet($url, ['dl' => 'true'], $headers, $options);
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

        $response = $this->requestGet($url);
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
     *                                      width, height,location, path, container,
     *                                      exif, uploaded (timestamp),
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
        $params = [];
        foreach ($fields as $field_name) {
            $params[$field_name] = "true";
        }

        $url .= "/metadata";

        // sign url if security is passed in
        if ($security) {
            $url = $security->signUrl($url);
        }

        $response = $this->requestGet($url, $params);
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
        $url = sprintf('%s/file/%s?key=%s', FilestackConfig::API_URL, $handle, $api_key);
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

        $response = $this->requestPost($url, $data);
        $filelink = $this->handleResponseCreateFilelink($response);

        return $filelink;
    }

    /**
     * Trigger the start of a multipart upload
     *
     * @param string        $api_key        Filestack API Key
     * @param string        $filename       explicitly set the filename
     * @param string        $mimetype       explicitly set the mimetype
     * @param int           $size           explicitly set the size in bytes
     * @param string        $store_location storage location, 's3' by default
     * @param FilestackSecurity $security       Filestack security object if
     *                                          security settings is turned on
     *
     * @throws FilestackException   if API call fails
     *
     * @return json
     */
    public function sendMultipartStart($api_key, $filename, $mimetype, $size,
        $location = 's3', $security = null)
    {
        $data = [];
        array_push($data, ['name' => 'apikey',          'contents' => $api_key]);
        array_push($data, ['name' => 'filename',        'contents' => $filename]);
        array_push($data, ['name' => 'mimetype',        'contents' => $mimetype]);
        array_push($data, ['name' => 'size',            'contents' => $size]);
        array_push($data, ['name' => 'store_location',  'contents' => $location]);

        array_push($data, ['name' => 'files', 'contents' => '', 'filename' => $filename]);
        $this->multipartApplySecurity($data, $security);

        $url = FilestackConfig::UPLOAD_URL . '/multipart/start';
        $response = $this->requestPost($url, ['multipart' => $data]);
        $json = $this->handleResponseDecodeJson($response);

        return $json;
    }

    /**
     * Upload a chunk of the file to server.
     *
     * @param string    $api_key        Filestack API Key
     * @param array     $jobs           array of jobs to process
     * @param FilestackSecurity $security       Filestack security object if
     *                                          security settings is turned on
     *
     * @throws FilestackException   if API call fails
     *
     * @return json
     */
    public function sendMultipartJobs($api_key, $filepath, $jobs,
        $location = 's3', $security = null)
    {
        $parts_etags = [];
        $promises = [];

        foreach ($jobs as $part_num => $job) {
            // build data
            $data = $this->createMultipartData($api_key, $job, $location);
            $this->multipartApplySecurity($data, $security);

            // append promise
            $promises[$part_num] = $this->http_client->requestAsync('POST',
                FilestackConfig::UPLOAD_URL . '/multipart/upload',
                ['multipart' => $data]);
        }

        // settle promises (concurrent async requests to filestack upload api)
        $upload_results = \GuzzleHttp\Promise\settle($promises)->wait();
echo "\nDONE with calliing uploads-api.  Calling s3 apis ....";

        $s3_promises = [];
        foreach ($upload_results as $result) {
            $json = $this->handleResponseDecodeJson($result['value']);

            $query = parse_url($json['url'], PHP_URL_QUERY);

            parse_str($query, $params);
            $part_num = $params['partNumber'];
echo "\npart_num: $part_num\n";

            $headers = $json['headers'];
            $chunk = $this->multipartGetChunk($filepath, $jobs[$part_num]['seek_point']);

            $s3_promises[] = $this->http_client->requestAsync('PUT', $json['url'], [
                'body' => $chunk,
                'headers' => $headers
            ]);
        }

        // settle promises (concurrent async requests to s3 api)
        $s3_results = \GuzzleHttp\Promise\settle($s3_promises)->wait();
echo "\nDONE wiht putting files to s3\n";

        foreach ($s3_results as $result) {
            $etag = $result['value']->getHeader('ETag')[0];
            $part_etag = sprintf('%s:%s', $job['part_num'], $etag);
            array_push($parts_etags, $part_etag);
        }

        $parts_etags = implode(';', $parts_etags);

        return $parts_etags;
    }

    /**
     * Trigger the end of a multipart upload
     *
     * @param string            $api_key        Filestack API Key
     * @param string            $job_uri        uri of job to mark as complete
     * @param string            $region         job region
     * @param string            $upload_id      upload id of job to marke as complete
     * @param string            $parts          parts of jobs and etags, semicolon separated
     * @param FilestackSecurity $security       Filestack security object if
     *                                          security settings is turned on
     *
     * @throws FilestackException   if API call fails
     *
     * @return json
     */
    public function sendMultipartComplete($api_key, $job_uri, $region, $upload_id,
        $parts, $filename, $mimetype, $size, $location = 's3', $security = null)
    {
        $data = [];
        array_push($data, ['name' => 'apikey',          'contents' => $api_key]);
        array_push($data, ['name' => 'parts',           'contents' => $parts]);
        array_push($data, ['name' => 'uri',             'contents' => $job_uri]);
        array_push($data, ['name' => 'region',          'contents' => $region]);
        array_push($data, ['name' => 'upload_id',       'contents' => $upload_id]);
        array_push($data, ['name' => 'filename',        'contents' => $filename]);
        array_push($data, ['name' => 'mimetype',        'contents' => $mimetype]);
        array_push($data, ['name' => 'size',            'contents' => $size]);
        array_push($data, ['name' => 'store_location',  'contents' => $location]);
        array_push($data, ['name' => 'files', 'contents' => '', 'filename' => $filename]);

        $this->multipartApplySecurity($data, $security);

        $url = FilestackConfig::UPLOAD_URL . '/multipart/complete';
        $response = $this->requestPost($url, ['multipart' => $data]);
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
     * Send POST request
     *
     * @param string    $url            url to post to
     * @param array     $data_to_send   data to send
     * @param array     $headers        optional headers to send
     */
    protected function requestPost($url, $data_to_send, $headers = [])
    {
        $headers['User-Agent'] = $this->user_agent_header;

        $data_to_send['headers'] = $headers;
        $data_to_send['http_errors'] = false;

        $response = $this->http_client->request('POST', $url, $data_to_send);
        return $response;
    }

    /**
     * Send PUT request
     *
     * @param string    $url            url to post to
     * @param array     $data_to_send   data to send
     * @param array     $headers        optional headers to send
     */
    protected function requestPut($url, $data_to_send, $headers = [])
    {
        $data_to_send['headers'] = $headers;
        $data_to_send['http_errors'] = false;

        $response = $this->http_client->request('PUT', $url, $data_to_send);
        return $response;
    }

    /**
     * Send GET request
     *
     * @param string    $url        url to post to
     * @param array     $params     optional params to send
     * @param array     $headers    optional headers to send
     */
    protected function requestGet($url, $params = [], $headers = [], $options = [])
    {
        $headers['User-Agent'] = $this->user_agent_header;
        $options['http_errors'] = false;
        $options['headers'] = $headers;

        // append question mark if there are optional params and ? doesn't exist
        if (count($params) > 0 && strrpos($url, '?') === false) {
            $url .= "?";
        }

        foreach ($params as $key => $value) {
            $url .= sprintf('&%s=%s', urlencode($key), urlencode($value));
        }

        $response = $this->http_client->request('GET', $url, $options);
        return $response;
    }

    /**
     * Send DELETE request
     *
     * @param string    $url        url to post to
     * @param array     $headers    optional headers to send
     * @param array     $options    optional options to send
     */
    protected function requestDelete($url, $headers = [], $options = [])
    {
        $headers['User-Agent'] = $this->getUserAgentHeader();
        $options['http_errors'] = false;
        $options['headers'] = $headers;

        $response = $this->http_client->request('DELETE', $url, $options);
        return $response;
    }

    /**
     * Get User Agent Header
     */
    protected function getUserAgentHeader()
    {
        if (!$this->user_agent_header) {
            $version = trim(file_get_contents(__DIR__ . '/../../VERSION'));
            $this->user_agent_header = sprintf('filestack-php-%s',
                $version);
        }
        return $this->user_agent_header;
    }

    private function createMultipartData($api_key, $job, $location)
    {
        $region     = $job['region'];
        $upload_id  = $job['upload_id'];
        $job_uri    = $job['uri'];
        $part_num   = $job['part_num'];
        $filename   = $job['filename'];

        $data = [];
        array_push($data, ['name' => 'apikey',          'contents' => $api_key]);
        array_push($data, ['name' => 'md5',             'contents' => $job['md5']]);
        array_push($data, ['name' => 'size',            'contents' => $job['filesize']]);
        array_push($data, ['name' => 'region',          'contents' => $job['region']]);
        array_push($data, ['name' => 'upload_id',       'contents' => $job['upload_id']]);
        array_push($data, ['name' => 'uri',             'contents' => $job['uri']]);
        array_push($data, ['name' => 'part',            'contents' => $job['part_num']]);
        array_push($data, ['name' => 'store_location',  'contents' => $location]);

        array_push($data, [
            'name'      => 'files',
            'contents'  => '',
            'filename'  => $job['filename']]);

        return $data;
    }

    protected function multipartGetChunk($filepath, $seek_point) {
        $fp = fopen($filepath, 'r');
        fseek($fp, $seek_point);
        $chunk = fread($fp, FilestackConfig::UPLOAD_CHUNK_SIZE);
        fclose($fp);
        $fp = null;

        return $chunk;
    }

    private function multipartApplySecurity(&$data, $security)
    {
        if ($security) {
            array_push($data, ['name' => 'policy', 'contents' => $security->policy]);
            array_push($data, ['name' => 'signature', 'contents' => $security->signature]);
        }
    }
}
