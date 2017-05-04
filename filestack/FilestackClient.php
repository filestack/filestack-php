<?php
namespace Filestack;

use Filestack\FilestackConfig;
use GuzzleHttp\Client;

/**
 * FilestackClient client.  This is the main object to
 * make functional calls to the Filestack API.
 */
class FilestackClient
{
    use Mixins\CommonMixin;

    public $api_key;

    /**
     * FilestackClient constructor
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
     * Get the cdn url of a filestack file
     *
     */
    public function getCdnUrl($handle) {
        $url = sprintf('%s/%s', FilestackConfig::CDN_URL, $handle);
        return $url;
    }

    /**
     * Get the content of file
     *
     * @param string            $url        Filestack file url or handle
     * @param FilestackSecurity $security   Filestack security object if
     *                                      security settings is turned on
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return string (file content)
     */
    public function getContent($url, $security=null)
    {
        if (!$this->isUrl($url)) { // CommonMixin
            $url = $this->getCdnUrl($url);
        }

        // call CommonMixin function
        $result = $this->sendGetContent($url);

        return $result;
    }

    /**
     * Get metadata of a file
     *
     * @param string            $url        Filestack file url or handle
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
     * @return json
     */
    public function getMetaData($url, $fields=[], $security=null)
    {
        if (!$this->isUrl($url)) { // CommonMixin
            $url = $this->getCdnUrl($url);
        }

        // call CommonMixin function
        $result = $this->sendGetMetaData($url, $fields);

        return $result;
    }

    /**
     * Download a file, saving it to specified destination
     *
     * @param string            $url            Filestack file url or handle
     * @param string            $destination    destination filepath to save to,
     *                                          can be folder name (defaults to stored filename)
     * @param FilestackSecurity $security       Filestack security object if
     *                                          security settings is turned on
     *
     * @throws FilestackException   if API call fails
     *
     * @return bool (true = download success, false = failed)
     */
    public function download($url, $destination, $security=null)
    {
        if (!$this->isUrl($url)) { // CommonMixin
            $url = $this->getCdnUrl($url);
        }

        // call CommonMixin function
        $result = $this->sendDownload($url, $destination, $security);

        return $result;
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
     * @param FilestackSecurity    $security   Filestack Security object
     *
     * @throws FilestackException   if API call fails
     *
     * @return Filestack\Filelink or null
     */
    public function store($filepath, $extras=[], $security=null)
    {
        // call CommonMixin function
        $filelink = $this->sendStore($filepath, $this->api_key, $extras, $security);

        return $filelink;
    }
}
