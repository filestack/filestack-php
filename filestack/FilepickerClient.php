<?php
namespace Filestack;

use GuzzleHttp\Client;
use Filestack\FilestackConfig;

/**
 * Filepicker client.  This is the main object to
 * make functional calls to the Filestack API.
 */
class FilepickerClient
{
    public $api_key;

    /**
     * FilepickerClient constructor
     *
     * @param string    $api_key    your Filestack API Key
     */
    function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    /**
     * store a file to desired cloud service, defaults to Filestack's S3
     * storage
     *
     * @param string    $filepath   real path to file
     *
     * @return response
     */
    public function store($filepath)
    {
        $client = new Client();

        $url = sprintf('%s/store/S3?key=%s',
            FilestackConfig::API_URL, $this->api_key);

        $body = fopen($filepath, 'r');
        $response = $client->post($url, ['body'=>$body]);

        return $response;
    }
}
