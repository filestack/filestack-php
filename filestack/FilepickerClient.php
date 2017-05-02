<?php
namespace Filestack;

use GuzzleHttp\Client;

/**
 * Filepicker client.  This is the main object to
 * make functional calls to the Filestack API.
 */
class FilepickerClient
{
    use Mixins\CommonMixin;

    public $api_key;

    /**
     * FilepickerClient constructor
     *
     * @param string    $api_key    your Filestack API Key
     */
    function __construct($api_key, $http_client=null)
    {
        $this->api_key = $api_key;
        if (is_null($http_client)) {
            $http_client = new Client();
        }
        $this->http_client = $http_client; // CommonMixin
    }
}
