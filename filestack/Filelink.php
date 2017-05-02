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
    use Mixins\ImageConversionMixin;

    public $api_key;
    public $handle;

    /**
     * Filelink constructor
     *
     * @param string    $handle     Filestack file handle
     * @param string    $api_key    Filestack API Key
     */
    function __construct($handle, $api_key='', $http_client=null)
    {
        $this->handle = $handle;
        $this->api_key = $api_key;

        if (is_null($http_client)) {
            $http_client = new Client();
        }
        $this->http_client = $http_client; // CommonMixin
    }

    /**
     * Store this file to desired cloud service, defaults to Filestack's S3
     * storage.  Set $extra['location'] to specify location.
     * Possible values are: S3, gcs, azure, rackspace, dropbox
     *
     * @param array                 $extras     extra optional params.  Allowed options are:
     *                                          location, filename, mimetype, path, container,
     *                                          access (public|private), base64decode (true|false)
     * @param Filestack\Security    $security   Filestack Security object
     *
     * @return Filestack\Filelink or null
     */
    public function store($extras=[], $security=null)
    {
        $filepath = $this->url();
        $filelink = $this->sendStore($filepath, $this->api_key, $extras, $security);

        return $filelink;
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
     * @param Filestack\Security    $security   Filestack security object
     *
     * @return string
     */
    public function signedUrl($security)
    {
        return sprintf('url?policy=%s&signature=%s',
            $security->policy, $security->signature);
    }
}
