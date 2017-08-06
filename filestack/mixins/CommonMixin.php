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
     * Get the miliseconds of exponential backoff retry strategy
     *
     * @param   int  $retry_num    the retry number
     *
     * @return int
     */
    public function get_retry_miliseconds($retry_num)
    {
        // (2^retries * 100) milliseconds
        return pow(2, $retry_num) * 100;
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
     * Get the safe for work (sfw) flag of a filelink.
     *
     * @param string            $handle     Filestack file handle
     * @param FilestackSecurity $security   Filestack security object if
     *                                      security settings is turned on
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return json
     */
    protected function sendGetSafeForWork($handle, $security)
    {
        $url = sprintf('%s/sfw/security=policy:%s,signature:%s/%s',
            FilestackConfig::CDN_URL,
            $security->policy,
            $security->signature,
            $handle);

        $response = $this->sendRequest('GET', $url);
        $status_code = $response->getStatusCode();

        if ($status_code !== 200) {
            throw new FilestackException($response->getBody(), $status_code);
        }

        $json_response = json_decode($response->getBody(), true);

        return $json_response;
    }

    /**
     * Get the tags of a filelink.
     *
     * @param string            $handle     Filestack file handle
     * @param FilestackSecurity $security   Filestack security object if
     *                                      security settings is turned on
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return json
     */
    protected function sendGetTags($handle, $security)
    {
        $url = sprintf('%s/tags/security=policy:%s,signature:%s/%s',
            FilestackConfig::CDN_URL,
            $security->policy,
            $security->signature,
            $handle);

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

    /**
     * Handles a response.  decode and return json if 200,
     * throws exception otherwise.
     *
     * @param Response  $response   the response object
     *
     * @throws FilestackException   if statuscode is not OK
     *
     * @return array (decoded json)
     */
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
     * Append source header to request headers array
     */
    protected function addRequestSourceHeader(&$headers)
    {
        $source_headers = $this->getSourceHeaders();
        $headers['User-Agent'] = $source_headers['user-agent'];
        $headers['Filestack-Source'] = $source_headers['filestack-source'];
    }

    /**
     * Append a data item
     */
    protected function appendData(&$data, $name, $value)
    {
        array_push($data, ['name' => $name, 'contents' => $value]);
    }

    /**
     *
     */
    protected function appendPromise(&$promises, $method, $url, $to_send)
    {
        $promises[] = $this->http_client->requestAsync($method,
                $url, $to_send);
    }

    protected function settlePromises($promises)
    {
        $api_results = \GuzzleHttp\Promise\settle($promises)->wait();
        return $api_results;
    }
}
