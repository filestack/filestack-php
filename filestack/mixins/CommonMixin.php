<?php
namespace Filestack\Mixins;

use Filestack\FilestackConfig;
use Filestack\Filelink;
use Filestack\FilestackException;

/**
 * Mixin for common functionalities used by most Filestack objects
 *
 */
trait CommonMixin
{
    protected $http_client;

    /**
     * CommonMixin constructor
     *
     * @param object    $http_client     Http client
     */
    public function __construct($http_client)
    {
        $this->http_client = $http_client;
    }

    /**
     * Download a file given a filestack file handle.
     *
     * @param string            $handle     Filestack file handle
     * @param Filetack\Security $security   Filestack security object if
     *                                      security settings is turned on
     */
    public function download($handle, $security=null)
    {
        # saves to local drive
    }

    /**
     * Get the content of a file.
     *
     * @param string            $handle     Filestack file handle
     * @param Filetack\Security $security   Filestack security object if
     *                                      security settings is turned on
     */
    public function get_content($handle, $security=null)
    {
        # return bytes
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
     * @param Filestack\Security    $security   Filestack Security object
     *
     * @throws FilestackException   if API call fails
     *
     * @return Filestack\Filelink or null
     */
    public function sendStore($filepath, $api_key, $options=[], $security=null)
    {
        // set filename to original file if one does not exists
        if (!array_key_exists('filename', $options)) {
            $options['filename'] = basename($filepath);
        }

        // build url and data to send
        $url = FilestackConfig::createUrl('store', $api_key, $options, $security);
        $data_to_send = $this->create_upload_file_data($filepath);

        // send post request
        $response = $this->http_client->request('POST', $url, $data_to_send);
        $status_code = $response->getStatusCode();

        // handle response
        if ($status_code == 200) {
            $json_response = json_decode($response->getBody(), $status_code);

            $url = $json_response['url'];
            $file_handle = substr($url, strrpos($url, '/') + 1);
            $filelink = new Filelink($file_handle);
            return $filelink;
        }
        else {
            throw new FilestackException($response->getBody(), $status_code);
        }

        return null;
    }

    /**
     * Check if a string is a valid url.
     *
     * @param   string  $url    url string to check
     *
     * @return bool
     */
    public function is_url($url) {
        $path = parse_url($url, PHP_URL_PATH);
        $encoded_path = array_map('urlencode', explode('/', $path));
        $url = str_replace($path, implode('/', $encoded_path), $url);

        return filter_var($url, FILTER_VALIDATE_URL) ? true : false;
    }

    /**
     * Creates data array to send to request based on if filepath is
     * real filepath or url
     *
     * @param   string  $filepath    filepath or url
     *
     * @return array
     */
    protected function create_upload_file_data($filepath) {
        $data = ['http_errors' => false];
        if ($this->is_url($filepath)) {
            // external source (passing url instead of filepath)
            $data['form_params'] = ['url' => $filepath];
        }
        else {
            // local file
            $data['body'] = fopen($filepath, 'r');
        }
        return $data;
    }
}
