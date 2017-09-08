<?php
namespace Filestack;

use GuzzleHttp\Client;
use Filestack\FilestackConfig;
use Filestack\UploadProcessor;

/**
 * Filestack client object.  This is the main object to
 * make functional calls to the Filestack API.
 */
class FilestackClient
{
    use Mixins\CommonMixin;
    use Mixins\TransformationMixin;

    public $api_key;
    public $security;
    private $upload_processor;

    /**
     * FilestackClient constructor
     *
     * @param string            $api_key        your Filestack API Key
     * @param FilestackSecurity $security       Filestack security object if
     *                                          security settings is turned on
     * @param GuzzleHttp\Client $http_client    DI http client, will instantiate
     *                                          one if not passed in
     * @param UploadProcessor   $upload_processor DI upload_processor object
     */
    public function __construct($api_key, $security = null, $http_client = null,
        $upload_processor = null)
    {
        $this->api_key = $api_key;
        $this->security = $security;

        if (is_null($http_client)) {
            $http_client = new Client();
        }
        $this->http_client = $http_client; // CommonMixin

        if (is_null($upload_processor)) {
            $upload_processor = new UploadProcessor($api_key, $security, $http_client);
        }
        $this->upload_processor = $upload_processor;
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
     *                                      uploaded (timestamp), writable,
     *                                      cloud, source_url
     *
     * @throws FilestackException   if API call fails
     *
     * @return array
     */
    public function getMetaData($url, $fields = [])
    {
        if (!$this->isUrl($url)) { // CommonMixin
            $url = $this->getCdnUrl($url);
        }

        // call CommonMixin function
        $result = $this->sendGetMetaData($url, $fields, $this->security);
        return $result;
    }

    /**
     * Get sfw (safe for work) flag of a filelink
     *
     * @param string            $handle     Filestack filelink handle
     *
     * @throws FilestackException   if API call fails
     *
     * @return json
     */
    public function getSafeForWork($handle)
    {
        // call CommonMixin function
        $result = $this->sendGetSafeForWork($handle, $this->security);
        return $result;
    }

    /**
     * Get tags of a filelink
     *
     * @param string            $handle     Filestack filelink handle
     *
     * @throws FilestackException   if API call fails
     *
     * @return json
     */
    public function getTags($handle)
    {
        // call CommonMixin function
        $result = $this->sendGetTags($handle, $this->security);
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
    public function collage($sources, $width, $height, $store_options = [],
        $color = 'white', $fit = 'auto', $margin = 10, $auto_rotate = false)
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
     * Convert url or filelink handle to another audio format.  IMPORTANT: To use
     * this function, you must setup webhooks to notify you when the transcoding
     * has completed. See our online documentation for more details:
     * https://www.filestack.com/docs/audio-transformations
     *
     * @param string    $resource   url or file handle
     * @param string    $format     The format to which you would like to
     *                              convert to. e.g (aac, hls, mp3, m4a, oga)
     * @param array     $options    Array of options.
     *                              access: public or private
     *                                Indicates that the file should be stored in
     *                                a way that allows public access going directly
     *                                to the underlying file store. For instance,
     *                                if the file is stored on S3, this will allow
     *                                the S3 url to be used directly. This has no
     *                                impact on the ability of users to read from
     *                                the Filestack file URL. Defaults to 'private'.
     *                              audio_bitrate: Sets the audio bitrate for the
     *                                audio file that is generated by the transcoding
     *                                process. Must be an integer between 0 and 999.
     *                              audio_channels: Set the number of audio channels
     *                                for the audio file that is generated by the
     *                                transcoding process. Can be an integer between
     *                                1 and 12. Default is same as source audio.
     *                              audio_sample_rate: Set the audio sample rate
     *                                for the audio file that is generated by the
     *                                transcoding process. Can be an integer between
     *                                0 and 99999. Default is 44100.
     *                              clip_length: Set the length of the audio file
     *                                that is generated by the transcoding process.
     *                                Format is hours:minutes:seconds e.g. (00:00:20)
     *                              clip_offset: Set the point to begin the audio
     *                                clip from. For example, clip_offset:00:00:10
     *                                will start the audio transcode 10 seconds
     *                                into the source audio file. Format is
     *                                hours:minutes:seconds, e.g. (00:00:10)
     *                              container: The bucket or container in the specified
     *                                file store where the file should end up.
     *                              extname: Set the file extension for the audio
     *                                file that is generated by the transcoding
     *                                process, e.g. ('.mp4', '.webm')
     *                              filename: Set the filename of the audio file
     *                                that is generated by the transcoding process.
     *                              force: (bool) set to true to restart completed
     *                                or pending audio encoding if a transcoding
     *                                fails, and you make the same request again
     *                              location: The custom storage service to store
     *                                the converted file to, options incude 'S3',
     *                                'azure', 'gcs', 'rackspace', and 'dropbox'
     *                              path: The path to store the file at within the
     *                                specified file store. For S3, this is the
     *                                key where the file will be stored at. By
     *                                default, Filestack stores the file at the
     *                                root at a unique id, followed by an underscore,
     *                                followed by the filename, for example
     *                                "3AB239102DB_myaudio.mp3"
     *                              title: Set the title in the file metadata.
     * @param bool      $force      set to true to restart completed
     *                              or pending audio encoding if a transcoding
     *                              fails, and you make the same request again
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return string (uuid of conversion task)
     */
    public function convertAudio($resource, $format, $options = [], $force = false)
    {
        $transform_tasks = [
            'video_convert' => $options
        ];

        // set filetype
        $transform_tasks['video_convert']['p'] = $format;

        // call TransformationMixin function
        $result = $this->sendVideoConvert($resource, $transform_tasks,
            $this->security, $force);

        return $result;
    }

    /**
     * Convert audio file from url or filelink handle to another format.  To see
     * which format can be converted, see:
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
     * @return Filestack/Filelink
     */
    public function convertFile($resource, $filetype, $options = [])
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
     * Convert a video file from url or filelink handle to another video format.
     * IMPORTANT: To use this function, you must setup webhooks to notify you
     * when the transcoding has completed. See our online docs for more details:
     * https://www.filestack.com/docs/video-transformations
     *
     * @param string    $resource   url or file handle
     * @param string    $format     The format to which you would like to
     *                              convert to. e.g (aac, h264, h264.hi,
     *                              hls.variant, hls.variant.audio, m4a, mp3,
     *                              oga, ogg, ogg.hi, webm, webm.hi)
     * @param array     $options    Array of options.
     *                              access: public or private
     *                                Indicates that the file should be stored in
     *                                a way that allows public access going directly
     *                                to the underlying file store. For instance,
     *                                if the file is stored on S3, this will allow
     *                                the S3 url to be used directly. This has no
     *                                impact on the ability of users to read from
     *                                the Filestack file URL. Defaults to 'private'.
     *                              aspect_mode: set the aspect mode (default: letterbox)
     *                                'preserve` - Original size and ratio is preserved.
     *                                'constrain' - Aspect ratio is maintained,
     *                                  No black bars are added to the output.
     *                                'crop' - Fills frame size and crops the rest
     *                                'letterbox' - Adds black bars to defined height
     *                                'pad' - Adds black bars to output to match
     *                                  defined frame size
     *                              audio_bitrate: Sets the audio bitrate for the
     *                                audio file that is generated by the transcoding
     *                                process. Must be an integer between 0 and 999.
     *                              audio_channels: Set the number of audio channels
     *                                for the audio file that is generated by the
     *                                transcoding process. Can be an integer between
     *                                1 and 12. Default is same as source audio.
     *                              audio_sample_rate: Set the audio sample rate
     *                                for the audio file that is generated by the
     *                                transcoding process. Can be an integer between
     *                                0 and 99999. Default is 44100.
     *                              clip_length: Set the length of the video file
     *                                that is generated by the transcoding process.
     *                                Format is hours:minutes:seconds e.g. (00:00:20)
     *                              clip_offset: Set the point to begin the video
     *                                clip from. For example, clip_offset:00:00:10
     *                                will start the audio transcode 10 seconds
     *                                into the source audio file. Format is
     *                                hours:minutes:seconds, e.g. (00:00:10)
     *                              container: The bucket or container in the specified
     *                                file store where the file should end up.
     *                              extname: Set the file extension for the audio
     *                                file that is generated by the transcoding
     *                                process, e.g. ('.mp4', '.webm')
     *                              fps: Specify the frames per second of the video
     *                                that is generated by the transcoding process.
     *                                Must be an integer between 1 and 300 Default
     *                                is to copy the original fps of the source file.
     *                              filename: Set the filename of the audio file
     *                                that is generated by the transcoding process.
     *                              keyframe_interval: Adds a key frame every 250
     *                                frames to the video that is generated by the
     *                                transcoding process. Default is 250.
     *                              location: The custom storage service to store
     *                                the converted file to, options incude 'S3',
     *                                'azure', 'gcs', 'rackspace', and 'dropbox'
     *                              path: The path to store the file at within the
     *                                specified file store. For S3, this is the
     *                                key where the file will be stored at. By
     *                                default, Filestack stores the file at the
     *                                root at a unique id, followed by an underscore,
     *                                followed by the filename, for example
     *                                "3AB239102DB_myaudio.mp3"
     *                              title: Set the title in the file metadata.
     *                              two_pass: Specify that the transcoding process
     *                                should do two passes to improve video quality.
     *                                Defaults to false.
     *                              width: Set the width in pixels of the video
     *                                that is generated by the transcoding process.
     *                              height: Set the height in pixels of the video
     *                                that is generated by the transcoding process.
     *                              upscale: Upscale the video resolution to match
     *                                your profile. Defaults to true.
     *                              video_bitrate: Specify the video bitrate for
     *                                the video that is generated by the transcoding
     *                                process. Must be an integer between 1 and 5000.
     *                              watermark_bottom: The distance from the bottom
     *                                of the video frame to place the watermark
     *                                on the video. (0 to 9999)
     *                              watermark_left: The distance from the left side
     *                                of the video frame to place the watermark
     *                                on the video. (0 to 9999)
     *                              watermark_right: The distance from the left side
     *                                of the video frame to place the watermark
     *                                on the video. (0 to 9999)
     *                              watermark_left: The distance from the top     *                                of the video frame to place the watermark
     *                                on the video. (0 to 9999)
     *                              watermark_width: Resize the width of the watermark
     *                              watermark_height: Resize the height of the watermark
     *                              watermark_url: The Filestack handle or URL of
     *                                the image file to use as a watermark on the
     *                                transcoded video.
     * @param bool      $force      set to true to restart completed
     *                              or pending audio encoding if a transcoding
     *                              fails, and you make the same request again
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return string (uuid of conversion task)
     */
    public function convertVideo($resource, $format, $options = [], $force = false)
    {
        $transform_tasks = [
            'video_convert' => $options
        ];

        // set filetype
        $transform_tasks['video_convert']['p'] = $format;

        // call TransformationMixin function
        $result = $this->sendVideoConvert($resource, $transform_tasks, $this->security, $force);

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
     * @return json response
     */
    public function debug($resource, $transform_tasks)
    {
        // call TransformationMixin functions
        $tasks_str = $this->createTransformStr($transform_tasks);
        $transform_url = $this->createTransformUrl(
            $this->api_key,
            'image',
            $resource,
            $tasks_str,
            $this->security
        );
        $json_response = $this->sendDebug($transform_url, $this->api_key, $this->security);

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
     * @param string            $destination    destination filepath to save to, can
     *                                          be foldername (defaults to stored filename)
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
        $filelink = $this->sendOverwrite($filepath, $handle,
            $this->api_key, $this->security);

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
    public function screenshot($url, $store_options = [],
        $agent = 'desktop', $mode = 'all', $width = 1024, $height = 768, $delay = 0)
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
     * storage.
     *
     * @param string    $filepath       path to file
     * @param array     $options        location: specify location, possible
     *                                    values are:
     *                                    S3, gcs, azure, rackspace, dropbox
     *                                  filename: explicitly set the filename
     *                                    to store as
     *                                  mimetype: explicitly set the mimetype
     *                                  intelligent: set to true to use the
     *                                    Intelligent Ingestion flow
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack\Filelink or file content
     */
    public function upload($filepath, $options = [])
    {
        if (!file_exists($filepath)) {
            throw new FilestackException("File not found", 400);
        }

        $location = 's3';
        if (array_key_exists('location', $options)) {
            $location = $options['location'];
        }

        $filename = basename($filepath);
        if (array_key_exists('filename', $options)) {
            $filename = $options['filename'];
        }

        $mimetype = mime_content_type($filepath);
        if (array_key_exists('mimetype', $options)) {
            $mimetype = $options['mimetype'];
        }

        $metadata = [
            'filepath' => $filepath,
            'filename' => $filename,
            'filesize' => filesize($filepath),
            'mimetype' => $mimetype,
            'location' => $location,
        ];

        // register job
        $upload_data = $this->upload_processor->registerUploadTask($this->api_key,
            $metadata, $this->security);

        // Intelligent Ingestion option
        if (array_key_exists('intelligent', $options) &&
            $this->upload_processor->intelligenceEnabled($upload_data)) {

            $this->upload_processor->setIntelligent($options['intelligent']);
        }

        $result = $this->upload_processor->run($this->api_key,
                                               $metadata,
                                               $upload_data);

        $filelink = $result['filelink'];

        return $filelink;
    }

    /**
     * Upload a url to desired cloud service, defaults to Filestack's S3
     * storage.  Set $options['location'] to specify location, possible values are:
     *                                      S3, gcs, azure, rackspace, dropbox
     *
     * @param string                $url        url of file to upload
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
    public function uploadUrl($resource, $options = [])
    {
        // lowercase all options
        $options = array_change_key_case($options, CASE_LOWER);

        // set filename to original file if one does not exists
        if (!array_key_exists('filename', $options)) {
            $options['filename'] = basename($resource);
        }

        // set location to S3 if not passed in
        $location = 'S3';
        if (array_key_exists('location', $options)) {
            $location = $options['location'];
        }

        // build endpoint url
        $url = sprintf('%s/store/%s?key=%s',
            FilestackConfig::API_URL,
            $location,
            $this->api_key);

        foreach ($options as $key => $value) {
            $url .= "&$key=$value";
        }

        // append security if exists
        if ($this->security) {
            $url = $this->security->signUrl($url);
        }

        // send post request
        $data = ['form_params' => ['url' => $resource]];
        $response = $this->sendRequest('POST', $url, $data);
        $filelink = $this->handleResponseCreateFilelink($response);

        return $filelink;
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
    public function zip($sources, $store_options = [])
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
