<?php
namespace Filestack;

use GuzzleHttp\Client;

/**
 * FilestackClient client.  This is the main object to
 * make functional calls to the Filestack API.
 */
class FilestackClient
{
    use Mixins\CommonMixin;

    public $api_key;
    public $security;

    /**
     * FilestackClient constructor
     *
     * @param string            $api_key        your Filestack API Key
     * @param FilestackSecurity $security       Filestack security object if
     *                                          security settings is turned on
     * @param GuzzleHttp\Client $http_client    DI http client, will instantiate
     *                                          one if not passed in
     */
    public function __construct($api_key,  $security=null, $http_client=null)
    {
        $this->api_key = $api_key;
        $this->security = $security;

        if (is_null($http_client)) {
            $http_client = new Client();
        }
        $this->http_client = $http_client; // CommonMixin
    }

    /**
     * Get the cdn url of a filestack file
     *
     */
    public function getCdnUrl($handle)
    {
        $url = sprintf('%s/%s', FilestackConfig::CDN_URL, $handle);
        return $url;
    }

    /**
     * Get the content of file
     *
     * @param string            $url        Filestack file url or handle
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return string (file content)
     */
    public function getContent($url)
    {
        if (!$this->isUrl($url)) { // CommonMixin
            $url = $this->getCdnUrl($url);
        }

        // call CommonMixin function
        $result = $this->sendGetContent($url, $this->security);

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
     *
     * @throws FilestackException   if API call fails
     *
     * @return array
     */
    public function getMetaData($url, $fields=[])
    {
        if (!$this->isUrl($url)) { // CommonMixin
            $url = $this->getCdnUrl($url);
        }

        // call CommonMixin function
        $result = $this->sendGetMetaData($url, $fields, $this->security);

        return $result;
    }

    /**
     * Delete a file from cloud storage
     *
     * @param string            $handle         Filestack file handle to delete
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return bool (true = delete success, false = failed)
     */
    public function delete($handle)
    {
        // call CommonMixin function
        $result = $this->sendDelete($handle, $this->api_key, $this->security);
        return $result;
    }

    /**
     * Download a file, saving it to specified destination
     *
     * @param string            $url            Filestack file url or handle
     * @param string            $destination    destination filepath to save to,
     *                                          can be folder name (defaults to stored filename)
     *
     * @throws FilestackException   if API call fails
     *
     * @return bool (true = download success, false = failed)
     */
    public function download($url, $destination)
    {
        if (!$this->isUrl($url)) { // CommonMixin
            $url = $this->getCdnUrl($url);
        }

        // call CommonMixin function
        $result = $this->sendDownload($url, $destination, $this->security);

        return $result;
    }

    /**
     * Overwrite a file in cloud storage
     *
     * @param string            $filepath   real path to file
     * @param string            $handle     Filestack file handle to overwrite
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack\Filelink
     */
    public function overwrite($filepath, $handle)
    {
        $filelink = $this->sendOverwrite($filepath, $handle, $this->api_key, $this->security);
        return $filelink;
    }

    /**
     * Upload a file to desired cloud service, defaults to Filestack's S3
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
     *
     * @throws FilestackException   if API call fails
     *
     * @return Filestack\Filelink or null
     */
    public function upload($filepath, $options=[])
    {
        // set filename to original file if one does not exists
        if (!array_key_exists('filename', $options)) {
            $options['filename'] = basename($filepath);
        }

        // build url and data to send
        $url = FilestackConfig::createUrl('upload', $this->api_key, $options, $this->security);
        $data_to_send = $this->createUploadFileData($filepath);

        // send post request
        $response = $this->requestPost($url, $data_to_send);
        $status_code = $response->getStatusCode();

        // handle response
        if ($status_code == 200) {
            $json_response = json_decode($response->getBody(), true);

            $url = $json_response['url'];
            $file_handle = substr($url, strrpos($url, '/') + 1);

            $filelink = new Filelink($file_handle, $this->api_key);
            $filelink->metadata['filename'] = $json_response['filename'];
            $filelink->metadata['size'] = $json_response['size'];
            $filelink->metadata['mimetype'] = $json_response['type'];

            return $filelink;
        } else {
            throw new FilestackException($response->getBody(), $status_code);
        }

        return null;
    }
}
