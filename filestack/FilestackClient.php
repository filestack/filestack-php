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
    use Mixins\TransformationMixin;

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
    public function __construct($api_key, $security=null, $http_client=null)
    {
        $this->api_key = $api_key;
        $this->security = $security;

        if (is_null($http_client)) {
            $http_client = new Client();
        }
        $this->http_client = $http_client; // CommonMixin
    }

    /**
     * Catchall function, throws Filestack Exception if method is not valid
     *
     * @throws FilestackException   method not found in allowed lists
     */
    public function __call($method, $args)
    {
        throw new FilestackException("$method() is not a valid method.", 400);
        return $this;
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
     * Set this Filelink's transform_url to include the collage task
     *
     * @param array    $sources     An array of Filestack file handles or external
     *                              urls. These are the images that will comprise
     *                              the other images in the collage. The order in
     *                              which they appear in the array dictates how the
     *                              images will be arranged.
     * @param int       $width          width of result image (1 to 10000)
     * @param int       $height         height of result image (1 to 10000)
     * @param string    $color          Border color for the collage. This can be a
     *                                  word or hex value, e.g. ('red' or 'FF0000')
     * @param string    $fit            auto or crop.  Allows you to control how the
     *                                  images in the collage are manipulated so that
     *                                  the final collage image will match the height
     *                                  and width parameters you set more closely.
     *                                  Using crop will produce a result that is closest
     *                                  to the height and width parameters you set.
     * @param int       $margin         Sets the size of the border between and around
     *                                  the images.  Range is 1 to 100.
     * @param bool      $auto_rotate    Setting this parameter to true automatically
     *                                  rotates all the images in the collage according
     *                                to their exif orientation data.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink or contents
     */
    public function collage($sources, $width, $height, $store_options=[],
        $color='white', $fit='auto', $margin=10, $auto_rotate=false)
    {

        // slice off first source as the filelink
        $first_source = array_shift($sources);

        $process_attrs = [
            'f' => json_encode($sources),
            'w' => $width,
            'h' => $height,
            'c' => $color,
            'i' => $fit,
            'm' => $margin,
            'a' => $auto_rotate
        ];

        $transform_tasks = [
            'collage' => $process_attrs
        ];

        if (!empty($store_options)) {
            $transform_tasks['store'] = $store_options;
        }

        // call TransformationMixin function
        $result = $this->sendTransform($first_source, $transform_tasks, $this->security);

        return $result;
    }

    /**
     * Convert url or filelink handle to another format.  To see which formats
     * can be converted, see:
     * https://www.filestack.com/docs/image-transformations/conversion
     *
     * @param string    $resource   url or file handle
     * @param string    $filetype   The format to which you would like to
     *                              convert the file. e.g (doc, docx, html, jpg,
     *                              ods, odt, pdf, png, svg, txt, webp)
     * @param array     $options    Array of options.
     *                              background: Set a background color when
     *                                converting transparent .png files into other
     *                                file types.  Can be a word or hex value, e.g.
     *                                ('white' or 'FFFFFF')
     *                              colorspace: RGB, CMYK or Input
     *                                By default we convert all the images to the
     *                                RGB color model in order to be web friendly.
     *                                However, we have added an option to preserve
     *                                the original colorspace.  This will work for *                                JPEGs and TIFFs.
     *                              compress: (bool) You can take advantage of
     *                                Filestack's image compression which utilizes
     *                                JPEGtran and OptiPNG. The value for this parameter
     *                                is boolean. If you want to compress your image
     *                                then the value should be true. Compression
     *                                is off/false by default.
     *                              density: (int, 1 to 500).  You can adjust the
     *                                density when converting documents like PowerPoint,
     *                                PDF, AI and EPS files to image formats like
     *                                JPG or PNG. This can improve the resolution
     *                                of the output image.
     *                              docinfo: (bool) Set this to true to get information
     *                                about a document, such as the number of pages
     *                                and the dimensions of the file.
     *                              page: (int, 1 to 10000) If you are converting
     *                                a file that contains multiple pages such as
     *                                PDF or powerpoint file, you can extract a
     *                                specific page using the page parameter.
     *                              pageformat: set the page size used for the layout
     *                                of the resultant document. This parameter can
     *                                be used when converting the format of one
     *                                document into PDF, PNG, or JPG. Possible values:
     *                                'A3', 'A4', 'A5', 'B4','B5',
     *                                'letter', 'legal', 'tabloid'
     *                              pageorientation: portrait or landscape
     *                                determine the orientation of the resulting
     *                                document. This parameter can be used when
     *                                converting the format of one document into
     *                                PDF, PNG, or JPG.
     *                              quality: (int, 1 to 100, or 'input')
     *                                You can change the quality (and reduce the
     *                                file size) of JPEG images by using the quality
     *                                parameter.  The quality is set to 100 by default.
     *                                A quality setting of 80 provides a nice balance
     *                                between file size reduction and image quality.
     *                                If the quality is instead set to "input" the
     *                                image will not be recompressed and the input
     *                                compression level of the jpg will be used.
     *                              secure: (bool)  This parameter applies to conversions
     *                                of HTML and SVG sources. When the secure parameter
     *                                is set to true, the HTML or SVG file will be
     *                                stripped of any insecure tags (HTML sanitization).
     *                                Default setting is false.
     *                              strip: (bool)  Set to true to remove any metadata
     *                                embedded in an image.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return bool (true = delete success, false = failed)
     */
    public function convertFile($resource, $filetype, $options=[])
    {
        $transform_tasks = [
            'output' => $options
        ];

        // set filetype
        $transform_tasks['output']['f'] = $filetype;

        // call TransformationMixin function
        $result = $this->sendTransform($resource, $transform_tasks, $this->security);

        return $result;
    }

    /**
     * Debug transform tasks
     *
     * @param string    $resource           url or file handle
     * @param array     $transform_tasks    Transformation tasks to debug
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return bool (true = delete success, false = failed)
     */
    public function debug($resource, $transform_tasks)
    {
        $tasks_str = $this->createTransformStr($transform_tasks);

        // build url
        $options['tasks_str'] = $tasks_str;
        $options['handle'] = $resource;

        $transform_url = FilestackConfig::createUrl('transform', $this->api_key, $options);

        // call TransformationMixin functions
        $json_response = $this->sendDebug($transform_url, $this->security);

        return $json_response;
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
     * Take a screenshot of a URL
     *
     * @param string    $url            URL to screenshot
     * @param string    $store_options  optional store values
     * @param string    $agent          desktop or mobile
     * @param string    $mode           all or window
     * @param int       $width          Designate the width of the browser window. The
     *                                  width is 1024 by default, but can be set to
     *                                  anywhere between 1 to 1920.
     * @param int       $height         Designate the height of the browser window.
     *                                  The height is 768 by default, but can be set
     *                                  to anywhere between 1 to 1080.
     * @param int       $delay          Tell URL Screenshot to wait x milliseconds before
     *                                  capturing the webpage. Sometimes pages take
     *                                  longer to load, so you may need to delay the
     *                                  capture in order to make sure the page is
     *                                  rendered before the screenshot is taken. The
     *                                  delay must be an integer between 0 and 10000.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function screenshot($url, $store_options=[],
        $agent='desktop', $mode='all', $width=1024, $height=768, $delay=0)
    {
        $process_attrs = [
            'a' => $agent,
            'm' => $mode,
            'w' => $width,
            'h' => $height,
            'd' => $delay
        ];

        $transform_tasks = [
            'urlscreenshot' => $process_attrs
        ];

        if (!empty($store_options)) {
            $transform_tasks['store'] = $store_options;
        }

        // call TransformationMixin function
        $result = $this->sendTransform($url, $transform_tasks, $this->security);

        return $result;
    }

    /**
     * Applied array of transformation tasks to a url
     *
     * @param string    $url                url to transform
     * @param array     $transform_tasks    array of transformation tasks and
     *                                      optional attributes per task
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack\Filelink or file content
     */
    public function transform($url, $transform_tasks)
    {
        // call TransformationMixin
        $result = $this->sendTransform($url, $transform_tasks, $this->security);
        return $result;
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
     * @return Filestack\Filelink
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

            $filelink = new Filelink($file_handle, $this->api_key, $this->security);
            $filelink->metadata['filename'] = $json_response['filename'];
            $filelink->metadata['size'] = $json_response['size'];
            $filelink->metadata['mimetype'] = $json_response['type'];

            return $filelink;
        } else {
            throw new FilestackException($response->getBody(), $status_code);
        }

        return null;
    }

    /**
     * Bundle an array of files into a zip file.  This task takes the file or files
     * that are passed in the array and compresses them into a zip file.  Sources can
     * be handles, urls, or a mix of both
     *
     * @param array     $sources        Filestack handles and urls to zip
     * @param array     $store_options  Optional store options
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink or file content
     */
    public function zip($sources, $store_options=[])
    {
        $transform_tasks = [
            'zip' => []
        ];

        if (!empty($store_options)) {
            $transform_tasks['store'] = $store_options;
        }

        $sources_str = '[' . implode(',', $sources) . ']';

        // call TransformationMixin
        $result = $this->sendTransform($sources_str, $transform_tasks, $this->security);
        return $result;
    }
}
