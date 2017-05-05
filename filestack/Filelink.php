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
    use Mixins\TransformationMixin {
        Mixins\TransformationMixin::__construct as transformationConstruct;
    }

    public $api_key;
    public $handle;
    public $metadata;

    /**
     * Filelink constructor
     *
     * @param string    $handle     Filestack file handle
     * @param string    $api_key    Filestack API Key
     */
    public function __construct($handle, $api_key='', $http_client=null)
    {
        $this->handle = $handle;
        $this->api_key = $api_key;
        $this->metadata = [];

        if (is_null($http_client)) {
            $http_client = new Client();
        }
        $this->http_client = $http_client; // CommonMixin
        $this->transformationConstruct();
    }

    /**
     * Get the content of filelink
     *
     * @param FilestackSecurity $security   Filestack security object if
     *                                      security settings is turned on
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return string (file content)
     */
    public function getContent($security=null)
    {
        // call CommonMixin function
        $result = $this->sendGetContent($this->url());

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
     * @param FilestackSecurity $security   Filestack security object if
     *                                      security settings is turned on
     *
     * @throws FilestackException   if API call fails
     *
     * @return array
     */
    public function getMetaData($fields=[], $security=null)
    {
        // call CommonMixin function
        $result = $this->sendGetMetaData($this->url(), $fields);

        foreach ($result as $key => $value) {
            $this->metadata[$key] = $value;
        }

        return $result;
    }

    /**
     * Delete this filelink from cloud storage
     *
     * @param FilestackSecurity $security       Filestack security object is
     *                                          required for this call
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return bool (true = delete success, false = failed)
     */
    public function delete($security)
    {
        // call CommonMixin function
        $result = $this->sendDelete($this->handle, $this->api_key, $security);
        return $result;
    }

    /**
     * Download filelink as a file, saving it to specified destination
     *
     * @param string            $handle         Filestack file handle
     * @param string            $destination    destination filepath to save to,
     *                                          can be folder name (defaults to stored filename)
     * @param FilestackSecurity $security       Filestack security object if
     *                                          security settings is turned on
     *
     * @throws FilestackException   if API call fails
     *
     * @return bool (true = download success, false = failed)
     */
    public function download($destination, $security=null)
    {
        // call CommonMixin function
        $result = $this->sendDownload($this->url(), $destination, $security);
        return $result;
    }

    /**
     * Store this file to desired cloud service, defaults to Filestack's S3
     * storage.  Set $extra['location'] to specify location.
     * Possible values are: S3, gcs, azure, rackspace, dropbox
     *
     * @param array                 $extras     extra optional params.  Allowed options are:
     *                                          location, filename, mimetype, path, container,
     *                                          access (public|private), base64decode (true|false)
     * @param FilestackSecurity    $security   Filestack Security object
     *
     * @throws FilestackException   if API call fails
     *
     * @return Filestack\Filelink or null
     */
    public function store($extras=[], $security=null)
    {
        $filepath = $this->url();

        // call CommonMixin function
        $filelink = $this->sendStore($filepath, $this->api_key, $extras, $security);

        return $filelink;
    }

    /**
     * Overwrite this filelink in cloud storage
     *
     * @param string            $filepath   real path to file
     * @param FilestackSecurity $security   Filestack security object is
     *                                      required for this call
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return boolean
     */
    public function overwrite($filepath, $security)
    {
        $result = $this->sendOverwrite($filepath,
            $this->handle, $this->api_key, $security);

        // update metadata
        $this->metadata['filename'] = $result->metadata['filename'];
        $this->metadata['mimetype'] = $result->metadata['mimetype'];
        $this->metadata['size'] = $result->metadata['size'];

        return true;
    }

    public function transform($transform_tasks, $destination=null, $security=null)
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
        $url = FilestackConfig::createUrl('transform', $this->api_key, $options, $security);

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
