<?php
namespace Filestack;

use GuzzleHttp\Client;
use Filestack\FilestackConfig;

/**
 * Class representing a filestack filelink object
 */
class Filelink
{
    use Mixins\CommonMixin;
    use Mixins\TransformationMixin;

    public $api_key;
    public $handle;
    public $metadata;
    public $security;

    /**
     * Filelink constructor
     *
     * @param string    $handle     Filestack file handle
     * @param string    $api_key    Filestack API Key
     */
    public function __construct($handle, $api_key='', $security=null, $http_client=null)
    {
        $this->handle = $handle;
        $this->api_key = $api_key;
        $this->security = $security;

        $this->metadata = [];

        if (is_null($http_client)) {
            $http_client = new Client();
        }
        $this->http_client = $http_client; // CommonMixin
    }

    /**
     * Get the content of filelink
     *
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return string (file content)
     */
    public function getContent()
    {
        // call CommonMixin function
        $result = $this->sendGetContent($this->url(), $this->security);

        return $result;
    }

    /**
     * Get metadata of filehandle
     *
     * @param array             $fields     optional, specific fields to retrieve.
     *                                      possible fields are:
     *                                      mimetype, filename, size, width, height,
     *                                      location, path, container, exif,
     *                                      uploaded (timestamp), writable, cloud, source_url
     *
     *
     * @throws FilestackException   if API call fails
     *
     * @return array
     */
    public function getMetaData($fields=[])
    {
        // call CommonMixin function
        $result = $this->sendGetMetaData($this->url(), $fields, $this->security);

        foreach ($result as $key => $value) {
            $this->metadata[$key] = $value;
        }

        return $result;
    }

    /**
     * Delete this filelink from cloud storage
     *
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return bool (true = delete success, false = failed)
     */
    public function delete()
    {
        // call CommonMixin function
        $result = $this->sendDelete($this->handle, $this->api_key, $this->security);
        return $result;
    }

    /**
     * Download filelink as a file, saving it to specified destination
     *
     * @param string            $handle         Filestack file handle
     * @param string            $destination    destination filepath to save to,
     *                                          can be folder name (defaults to stored filename)
     *
     * @throws FilestackException   if API call fails
     *
     * @return bool (true = download success, false = failed)
     */
    public function download($destination)
    {
        // call CommonMixin function
        $result = $this->sendDownload($this->url(), $destination, $this->security);
        return $result;
    }

    /**
     * Overwrite this filelink in cloud storage
     *
     * @param string            $filepath   real path to file
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return boolean
     */
    public function overwrite($filepath)
    {
        $result = $this->sendOverwrite($filepath,
            $this->handle, $this->api_key, $this->security);

        // update metadata
        $this->metadata['filename'] = $result->metadata['filename'];
        $this->metadata['mimetype'] = $result->metadata['mimetype'];
        $this->metadata['size'] = $result->metadata['size'];

        return true;
    }

    public function crop($options)
    {

    }

    public function transform($transform_tasks, $destination=null)
    {
        // build tasks_str
        $tasks_str = '';
        $num_tasks = count($transform_tasks);
        $num_tasks_attached = 0;

        foreach ($transform_tasks as $taskname => $task_attrs) {
            // call TransformationMixin function to chain tasks
            $tasks_str .= $this->getTransformStr($taskname, $task_attrs);

            if ($num_tasks_attached < $num_tasks - 1) {
                $tasks_str .= "/"; // task separator
            }
            $num_tasks_attached++;
        }

        // build url
        $options['tasks_str'] = $tasks_str;
        $options['handle'] = $this->handle;
        $url = FilestackConfig::createUrl('transform', $this->api_key, $options, $this->security);

        $params = [];
        $headers = [];
        $req_options = [];

        if ($destination) {
            $req_options['sink'] = $destination;
        };

        // call CommonMixin function
        $response = $this->requestGet($url, $params, $headers, $req_options);
        $status_code = $response->getStatusCode();

        // handle response
        if ($status_code == 200) {
            if (!$destination) { // return content
                $content = $response->getBody()->getContents();
                return $content;
            }
        } else {
            throw new FilestackException($response->getBody(), $status_code);
        }

        return true;
    }

    /**
     * return the URL (cdn) of this filelink
     *
     * @return string
     */
    public function url()
    {
        return sprintf('%s/%s', FilestackConfig::CDN_URL, $this->handle);
    }

    /**
     * return the URL (cdn) of this filelink with security policy
     *
     * @param FilestackSecurity    $security   Filestack security object
     *
     * @return string
     */
    public function signedUrl($security)
    {
        return sprintf('%s?policy=%s&signature=%s',
            $this->url(),
            $security->policy,
            $security->signature);
    }
}
