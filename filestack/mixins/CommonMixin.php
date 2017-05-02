<?php
namespace Filestack\Mixins;

use Filestack\FilestackConfig;
use Filestack\Filelink;
use Filestack\FilestackException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

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
     * storage.
     *
     * @param string    $filepath   real path to file
     *
     * @throws FilestackException   if API call fails
     *
     * @return Filestack\Filelink or null
     */
    public function store($filepath)
    {

        $url = sprintf('%s/store/S3?key=%s',
            FilestackConfig::API_URL, $this->api_key);

        $body = fopen($filepath, 'r');

        /*
         * Make http requests, if exception encountered catch it and
         * throw FilestackException instead
         */

        $response = $this->http_client->request('POST', $url, ['body'=>$body, 'http_errors'=>false]);
        $status_code = $response->getStatusCode();

        if ($status_code == 200) {
            $json_response = json_decode($response->getBody(), $status_code);

            $url = $json_response['url'];
            $file_handle = substr($url, strrpos($url, '/') + 1);
            return new Filelink($file_handle);
        }
        else {
            throw new FilestackException($response->getBody(), $status_code);
        }

        return null;
    }
}
