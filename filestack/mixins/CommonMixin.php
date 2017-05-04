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
     * CommonMixin constructor
     *
     * @param object    $http_client     Http client
     */
    public function __construct($http_client)
    {
        $this->http_client = $http_client;
        $this->user_agent_header = sprintf('filestack-php-%s', FilestackConfig::getVersion());
    }

    /**
     * Check if a string is a valid url.
     *
     * @param   string  $url    url string to check
     *
     * @return bool
     */
    public function isUrl($url) {
        $path = parse_url($url, PHP_URL_PATH);
        $encoded_path = array_map('urlencode', explode('/', $path));
        $url = str_replace($path, implode('/', $encoded_path), $url);

        return filter_var($url, FILTER_VALIDATE_URL) ? true : false;
    }

    /**
     * Delete a file from cloud storage
     *
     * @param string            $handle         Filestack file handle to delete
     * @param string                $api_key    Filestack API Key
     * @param FilestackSecurity $security       Filestack security object is
     *                                          required for this call
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return bool (true = delete success, false = failed)
     */
    public function sendDelete($handle, $api_key, $security)
    {
        $options = ['handle' => $handle];
        $url = FilestackConfig::createUrl('delete', $api_key, $options, $security);
        $response = $this->requestDelete($url);
        $status_code = $response->getStatusCode();

        // handle response
        if ($status_code == 200) {
            return true;
        }
        else {
            throw new FilestackException($response->getBody(), $status_code);
        }

        // failed if reached
        return false;
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
    protected function sendDownload($url, $destination, $security=null)
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
        $options = ["sink" => $destination];

        $response = $this->requestGet($url, ["dl" => "true"], $headers, $options);
        $status_code = $response->getStatusCode();

        // handle response
        if ($status_code == 200) {
            return true;
        }
        else {
            throw new FilestackException($response->getBody(), $status_code);
        }

        // failed if reached
        return false;
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
    protected function sendGetContent($url, $security=null)
    {
        // sign url if security is passed in
        if ($security) {
            $url = $security->signUrl($url);
        }

        $response = $this->requestGet($url);
        $status_code = $response->getStatusCode();

        // handle response
        if ($status_code == 200) {
            $content = $response->getBody()->getContents();
            return $content;
        }
        else {
            throw new FilestackException($response->getBody(), $status_code);
        }

        // failed if reached
        return false;
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
    protected function sendGetMetaData($url, $fields=[], $security=null)
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

        // handle response
        if ($status_code == 200) {
            $json_response = json_decode($response->getBody(), true);
            return $json_response;
        }
        else {
            throw new FilestackException($response->getBody(), $status_code);
        }

        // failed if reached
        return false;
    }

    /**
     * store a file to desired cloud service, defaults to Filestack's S3
     * storage.  Set $options['location'] to specify location, possible values are:
     *                                      S3, gcs, azure, rackspace, dropbox
     *
     * @param string                $filepath   url or filepath
     * @param string                $api_key    Filestack API Key
     * @param array                 $options     extra optional params. e.g.
     *                                  location (string, storage location),
     *                                  filename (string, custom filename),
     *                                  mimetype (string, file mimetype),
     *                                  path (string, path in cloud container),
     *                                  container (string, container in bucket),
     *                                  access (string, public|private),
     *                                  base64decode (bool, true|false)
     * @param FilestackSecurity     $security   Filestack Security object
     *
     * @throws FilestackException   if API call fails
     *
     * @return Filestack\Filelink or null
     */
    protected function sendStore($filepath, $api_key, $options=[], $security=null)
    {
        // set filename to original file if one does not exists
        if (!array_key_exists('filename', $options)) {
            $options['filename'] = basename($filepath);
        }

        // build url and data to send
        $url = FilestackConfig::createUrl('store', $api_key, $options, $security);
        $data_to_send = $this->createUploadFileData($filepath);

        // send post request
        $response = $this->requestPost($url, $data_to_send);
        $status_code = $response->getStatusCode();

        // handle response
        if ($status_code == 200) {
            $json_response = json_decode($response->getBody(), true);

            $url = $json_response['url'];
            $file_handle = substr($url, strrpos($url, '/') + 1);

            $filelink = new Filelink($file_handle, $api_key);
            $filelink->metadata['filename'] = $json_response['filename'];
            $filelink->metadata['size'] = $json_response['size'];
            $filelink->metadata['mimetype'] = $json_response['type'];
            $filelink->metadata['path'] = $json_response['key'];
            $filelink->metadata['container'] = $json_response['container'];

            return $filelink;
        }
        else {
            throw new FilestackException($response->getBody(), $status_code);
        }

        return null;
    }

    /**
     * Overwrite a file in cloud storage
     *
     * @param string            $filepath   real path to file
     * @param string            $handle     Filestack file handle to overwrite
     * @param string            $api_key    Filestack API Key
     * @param FilestackSecurity $security   Filestack security object is
     *                                      required for this call
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack\Filelink
     */
    public function sendOverwrite($filepath, $handle, $api_key, $security)
    {
        $data_to_send = $this->createUploadFileData($filepath);

        $options = ['handle' => $handle];
        $url = FilestackConfig::createUrl('overwrite', $api_key, $options, $security);

        $response = $this->requestPost($url, $data_to_send);
        $status_code = $response->getStatusCode();

        // handle response
        if ($status_code == 200) {

            $json_response = json_decode($response->getBody(), true);

            $url = $json_response['url'];
            $file_handle = substr($url, strrpos($url, '/') + 1);

            $filelink = new Filelink($file_handle, $api_key);
            $filelink->metadata['filename'] = $json_response['filename'];
            $filelink->metadata['size'] = $json_response['size'];
            $filelink->metadata['mimetype'] = $json_response['mimetype'];

            return $filelink;
        }
        else {
            throw new FilestackException($response->getBody(), $status_code);
        }

        // failed if reached
        return false;
    }

    /**
     * Creates data array to send to request based on if filepath is
     * real filepath or url
     *
     * @param   string  $filepath    filepath or url
     *
     * @return array
     */
    protected function createUploadFileData($filepath) {
        $data = [];

        if ($this->isUrl($filepath)) {
            // external source (passing url instead of filepath)
            $data['form_params'] = ['url' => $filepath];
        }
        else {
            // local file
            $data['body'] = fopen($filepath, 'r');
        }
        return $data;
    }

    /**
     * Send POST request
     *
     * @param string    $url            url to post to
     * @param array     $data_to_send   data to send
     * @param array     $headers        optional headers to send
     */
    protected function requestPost($url, $data_to_send, $headers=[])
    {

        $headers['User-Agent'] = $this->user_agent_header;

        $data_to_send['headers'] = $headers;
        $data_to_send['http_errors'] = false;

        $response = $this->http_client->request('POST', $url, $data_to_send);
        return $response;
    }

    /**
     * Send GET request
     *
     * @param string    $url        url to post to
     * @param array     $params     optional params to send
     * @param array     $headers    optional headers to send
     */
    protected function requestGet($url, $params=[], $headers=[], $options=[])
    {
        $headers['User-Agent'] = $this->user_agent_header;
        $options['http_errors'] = false;
        $options['headers'] = $headers;

        // append question mark if there are optional params and ? doesn't exist
        if (count($params) > 0 && strrpos($url, '?') === false) {
            $url .= "?";
        }

        foreach ($params as $key => $value) {
            $url .= "&$key=$value";
        }

        $response = $this->http_client->request('GET', $url, $options);
        return $response;
    }

    /**
     * Send DELETE request
     *
     * @param string    $url        url to post to
     * @param array     $params     optional params to send
     * @param array     $headers    optional headers to send
     */
    protected function requestDelete($url, $params=[], $headers=[], $options=[])
    {
        $headers['User-Agent'] = $this->user_agent_header;
        $options['http_errors'] = false;
        $options['headers'] = $headers;

        // append question mark if there are optional params and ? doesn't exist
        if (count($params) > 0 && strrpos($url, '?') === false) {
            $url .= "?";
        }

        foreach ($params as $key => $value) {
            $url .= "&$key=$value";
        }

        $response = $this->http_client->request('DELETE', $url, $options);
        return $response;
    }
}
