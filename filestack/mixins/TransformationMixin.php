<?php
namespace Filestack\Mixins;

use Filestack\FilestackConfig;
use Filestack\FilestackException;
use Filestack\Filelink;

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
        if (!array_key_exists($taskname, FilestackConfig::ALLOWED_ATTRS)) {
            throw new FilestackException('Invalid transformation task', 400);
        }

        $this->validateAttributes($taskname, $process_attrs);

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
     * @param string    $transform_str           url or filestack handle to transform
     * @param array     $transform_tasks    array of transformation tasks and
     *                                      optional attributes per task
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return json object
     */
    public function sendDebug($transform_url, $security = null)
    {
        $transform_str = str_replace(FilestackConfig::CDN_URL . '/', '', $transform_url);
        $options = ['transform_str' => $transform_str];
        $debug_url = $this->filestack_config->createUrl('debug', $this->api_key, $options, $security);

        // call CommonMixin function
        $response = $this->requestGet($debug_url);
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
     * @param string    $resource           url or filestack handle to transform
     * @param array     $transform_tasks    array of transformation tasks and
     *                                      optional attributes per task
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

        // build url
        $options['tasks_str'] = $tasks_str;
        $options['handle'] = $resource;

        $url = $this->filestack_config->createUrl('transform', $this->api_key, $options, $security);

        // call CommonMixin function
        $response = $this->requestGet($url);
        $filelink = $this->handleResponseCreateFilelink($response);

        return $filelink;
    }

    /**
     * Send video_convert request to API
     *
     * @param string    $resource           url or filestack handle to convert
     * @param array     $transform_tasks    array of transformation tasks and
     *                                      optional attributes per task
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return string (uuid of conversion task)
     */
    public function sendVideoConvert($resource, $transform_tasks, $security = null)
    {
        $tasks_str = $this->createTransformStr($transform_tasks);

        // build url
        $options['tasks_str'] = $tasks_str;
        $options['handle'] = $resource;

        $url = $this->filestack_config->createUrl('transform', $this->api_key, $options, $security);

        // call CommonMixin function
        $response = $this->requestGet($url);
        $status_code = $response->getStatusCode();

        // handle response
        if ($status_code !== 200) {
            throw new FilestackException($response->getBody(), $status_code);
        }

        $json_response = json_decode($response->getBody(), true);
        $uuid = $json_response['uuid'];

        return $uuid;
    }

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
     * Validate the attributes of a transformation task
     *
     * @param string    $taskname   task name, e.g. "resize, crop, etc."
     * @param array     $attrs      attributes  attributes to validate
     *
     * @throws Filestack\FilestackException     if attribute is not on allowed list
     *
     * @return bool
     */
    protected function validateAttributes($taskname, $attrs)
    {
        foreach ($attrs as $key => $value) {
            if (!in_array($key, FilestackConfig::ALLOWED_ATTRS[$taskname])) {
                throw new FilestackException(
                    "Invalid transformation attribute $key for $taskname",
                    400
                );
            }
        }

        return true;
    }
}
