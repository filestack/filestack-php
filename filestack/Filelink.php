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
    public $transform_url;

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
     * Catchall function, handles allowed transformation and conversion functions
     *
     * @throws FilestackException   method not found in allowed lists
     */
    public function __call($method, $args)
    {
        // transformation calls
        if (in_array($method, FilestackConfig::ALLOWED_TRANSFORMATIONS)) {
            $options = [];
            if (count($args) > 0) {
                $options = $args[0];
            }
            $this->setTransformUrl($method, $options);
        } else {
            throw new FilestackException("$method() is not a valid method.", 400);
        }

        return $this;
    }

    /**
     * Get the content of filelink
     *
     * @param bool      $is_transformation  optional flag, set to true if downloading
     *                                      transformation_url
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
     * Get the transformed content of filelink
     *
     * @param bool      $is_transformation  optional flag, set to true if downloading
     *                                      transformation_url
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return string (file content)
     */
    public function getTransformedContent()
    {
        // call CommonMixin function
        $result = $this->sendGetContent($this->transform_url);
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
     * @param string    $destination        destination filepath to save to,
     *                                      can be folder name (defaults to stored filename)
     *
     * @throws FilestackException   if API call fails
     *
     * @return bool (true = download success, false = failed)
     */
    public function download($destination, $is_transformation=false)
    {
        // call CommonMixin function
        $result = $this->sendDownload($this->url(), $destination, $this->security);
        return $result;
    }

    /**
     * Download transformed filelink as a file, saving it to specified destination
     *
     * @param string    $destination        destination filepath to save to,
     *                                      can be folder name (defaults to stored filename)
     *
     * @throws FilestackException   if API call fails
     *
     * @return bool (true = download success, false = failed)
     */
    public function downloadTransformed($destination)
    {

        // call CommonMixin function
        $result = $this->sendDownload($this->transform_url, $destination);
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

    /**
     * Reset the transformation url of this Filelink.  Call this function if
     * you are calling multiple transformations on the same filelink without
     * using the transform method.
     */
    public function resetTransform()
    {
        $this->transform_url = null;
    }

    /**
     * Append or Create a task to the transformation url for this filelink
     *
     * @param array $options    task options, e.g. ['b' => '00FF00', 'd' => '45']
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return void
     */
    public function setTransformUrl($method, $options)
    {
        $this->initTransformUrl();
        $this->transform_url = $this->insertTransformStr($this->transform_url, $method, $options);
    }

    public function store($options=[])
    {
        $this->initTransformUrl();
        $this->transform_url = $this->insertTransformStr($this->transform_url, 'store', $options);

        // call CommonMixin function
        $response = $this->requestGet($this->transform_url);
        $status_code = $response->getStatusCode();

        // handle response
        if ($status_code == 200) {
            $json_response = json_decode($response->getBody(), true);

            $url = $json_response['url'];
            $file_handle = substr($url, strrpos($url, '/') + 1);

            $filelink = new Filelink($file_handle, $this->api_key, $this->security);
            $filelink->metadata['filename'] = $json_response['filename'];
            $filelink->metadata['size'] = $json_response['size'];
            $filelink->metadata['mimetype'] = $json_response['type'];

            return $filelink;
        } else {
            throw new FilestackException($response->getBody(), $status_code);
        }

        // error if reached
        return false;
    }

    /**
     * Applied array of transformation tasks to this file link.
     *
     * @param array     $transform_tasks    array of transformation tasks and
     *                                      optional attributes per task
     * @param string   $destination        option real path to where to save
     *                                      transformed file
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack\Filelink or contents
     */
    public function transform($transform_tasks, $destination=null)
    {
        // call TransformationMixin
        $result = $this->sendTransform($this->handle, $transform_tasks, $destination);
        return $result;
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

    /**
     * Initialize transform url if it doesnt exist
     */
    protected function initTransformUrl()
    {
        if (!$this->transform_url) {
            // security in a different format for transformations
            $security_str = $this->security ? sprintf('/security=policy:%s,signature:%s',
                    $this->security->policy,
                    $this->security->signature) : '';

            $this->transform_url = sprintf(FilestackConfig::PROCESSING_URL . '%s/%s',
                $security_str,
                $this->handle);
        }
    }
}
