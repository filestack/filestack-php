<?php
namespace Filestack\Mixins;

use Filestack\FilestackConfig;
use Filestack\FilestackException;
use Filestack\Filelink;

/**
 * Mixin for common transformation functionalities
 */
trait TransformationMixin
{
    /**
     * Return the URL portion of a transformation task
     *
     * @param string    $taskname       name of task, e.g. 'crop', 'resize', etc.
     * @param array     $process_attrs  attributes replated to this task
     *
     * @throws Filestack\FilestackException
     *
     * @return Transformation object
     */
    public function getTransformStr($taskname, $process_attrs)
    {
        $tranform_str = $taskname;
        if (count($process_attrs) > 0) {
            $tranform_str .= '=';
        }

        // append attributes if exists
        foreach ($process_attrs as $key => $value) {
            $encoded_value = gettype($value) === 'string' ?
                urlencode($value) : urlencode(json_encode($value));

            $tranform_str .= sprintf('%s:%s,',
                urlencode($key),
                $encoded_value);
        }

        // remove last comma
        if (count($process_attrs) > 0) {
            $tranform_str = substr($tranform_str, 0, strlen($tranform_str) - 1);
        }

        return $tranform_str;
    }

    /**
     * Insert a transformation task into existing url
     *
     * @param string    $url            url to insert task into
     * @param string    $taskname       name of task, e.g. 'crop', 'resize', etc.
     * @param array     $process_attrs  attributes replated to this task
     *
     * @throws Filestack\FilestackException
     *
     * @return Transformation object
     */
    protected function insertTransformStr($url, $taskname, $process_attrs = [])
    {
        $transform_str = $this->getTransformStr($taskname, $process_attrs);

        // insert transform_url before file handle
        $url = substr($url, 0, strrpos($url, '/'));

        return "$url/$transform_str/" . $this->handle;
    }

    /**
     * Send debug call
     *
     * @param string                $transform_url  the transformation url
     * @param string                $api_key        Filestack API Key
     * @param FilestackSecurity     $security       Filestack Security object if
     *                                              enabled
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return json object
     */
    public function sendDebug($transform_url, $api_key, $security = null)
    {
        $transform_str = str_replace(FilestackConfig::CDN_URL . '/', '', $transform_url);
        $debug_url = sprintf('%s/%s/debug/%s', FilestackConfig::CDN_URL,
            $api_key, $transform_str);

        if ($security) {
            $debug_url = $security->signUrl($debug_url);
        }

        // call CommonMixin function
        $response = $this->sendRequest('GET', $debug_url);
        $status_code = $response->getStatusCode();

        // handle response
        if ($status_code !== 200) {
            throw new FilestackException($response->getBody(), $status_code);
        }

        $json_response = json_decode($response->getBody(), true);

        return $json_response;
    }

    /**
     * Applied array of transformation tasks to handle or external url
     *
     * @param string            $resource           url or filestack handle
     * @param array             $transform_tasks    array of transformation tasks
     * @param FilestackSecurity $security           Filestack Security object if
     *                                              enabled
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack\Filelink
     */
    public function sendTransform($resource, $transform_tasks, $security = null)
    {
        // add store method if one does not exists
        if (!array_key_exists('store', $transform_tasks)) {
            $transform_tasks['store'] = [];
        }

        $tasks_str = $this->createTransformStr($transform_tasks);
        $transform_url = $this->createTransformUrl(
            $this->api_key,
            'image',
            $resource,
            $tasks_str,
            $security
        );

        // call CommonMixin function
        $response = $this->sendRequest('GET', $transform_url);
        $filelink = $this->handleResponseCreateFilelink($response);

        return $filelink;
    }

    /**
     * Send video_convert request to API
     *
     * @param string            $resource           url or filestack handle
     * @param array             $transform_tasks    array of transformation tasks
     * @param FilestackSecurity $security           Filestack Security object if
     *                                              enabled
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return string (uuid of conversion task)
     */
    public function sendVideoConvert($resource, $transform_tasks,
                                     $security = null, $force = false)
    {
        $tasks_str = $this->createTransformStr($transform_tasks);
        $transform_url = $this->createTransformUrl(
            $this->api_key,
            'video',
            $resource,
            $tasks_str,
            $security
        );

        // force restart task?
        if ($force) {
            $transform_url .= '&force=true';
        }

        // call CommonMixin function
        $response = $this->sendRequest('GET', $transform_url);
        $status_code = $response->getStatusCode();

        // handle response
        if ($status_code !== 200) {
            throw new FilestackException($response->getBody(), $status_code);
        }

        $json_response = json_decode($response->getBody(), true);
        $uuid = $json_response['uuid'];

        return [
            'uuid'              => $uuid,
            'conversion_url'    => $transform_url
        ];
    }

    /**
     * Get the info of a conversion task given the conversion url
     *
     * @param string     $conversion_url    the conversion task url
     *
     * @throws FilestackException   if API call fails
     *
     * @return json
     */
    public function getConvertTaskInfo($conversion_url)
    {
        $response = $this->sendRequest('GET', $conversion_url);
        $json = $this->handleResponseDecodeJson($response);

        return $json;
    }

    /**
     * Create the transform parts of the transformation url
     *
     * @param array     $transform_tasks    array of transformation tasks and
     *                                      optional attributes per task
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return string
     */
    protected function createTransformStr($transform_tasks)
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

        return $tasks_str;
    }

    /**
     * Create the transform parts of the transformation url
     *
     * @param string            $api_key    Filestack API Key
     * @param string            $type       Type of transformation:
     *                                      image, audio, video
     * @param sring             $resource   url or Filestack handle
     * @param string            $tasks_str  tranformation tasks part of url
     * @param FilestackSecurity $security   Filestack Security object if needed
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return string
     */
    protected function createTransformUrl($api_key, $type, $resource,
                                          $tasks_str, $security = null)
    {
        $api_host = $type == 'image' ?
            FilestackConfig::CDN_URL : FilestackConfig::PROCESS_URL;

        $base_url = sprintf('%s/%s',
            $api_host,
            $api_key);

        // security in a different format for transformations
        $security_str = $security ? sprintf('/security=policy:%s,signature:%s',
                $security->policy,
                $security->signature) : '';

        // build url for transform or zip
        $transform_url = sprintf($base_url . $security_str . '/%s/%s',
            $tasks_str,
            $resource
        );

        return $transform_url;
    }
}
