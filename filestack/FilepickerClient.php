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

    /**
     * Store a file to desired cloud service, defaults to Filestack's S3
     * storage.  Set $extra['location'] to specify location.
     * Possible values are: S3, gcs, azure, rackspace, dropbox
     *
     * @param string    $filepath   real path to file
     * @param array     $extras     extra optional params.  Allowed params are:
     *                              location (storage location: possible values are:
     *                                  S3, gcs, azure, rackspace, dropbox),
     *                              filename, mimetype, path, container,
     *                              access (public|private), base64decode (true|false)
     * @param Filestack\Security    $security   Filestack Security object
     *
     * @throws FilestackException   if API call fails
     *
     * @return Filestack\Filelink or null
     */
    public function store($filepath, $extras=[], $security=null)
    {
        $filelink = $this->sendStore($filepath, $this->api_key, $extras, $security);
        return $filelink;
    }
}
