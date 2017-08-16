<?php
namespace Filestack;

use GuzzleHttp\Client;
use Filestack\FilestackConfig;

/**
 * Class representing a Filestack Filelink object
 */
class Filelink
{
    use Mixins\CommonMixin;
    use Mixins\TransformationMixin;

    public $api_key;
    public $handle;
    public $metadata;
    public $security;
    public $transform_url;

    /**
     * Filelink constructor
     *
     * @param string    $handle     Filestack file handle
     * @param string    $api_key    Filestack API Key
     */
    public function __construct($handle, $api_key = '', $security = null,
                                $http_client = null)
    {
        $this->handle = $handle;
        $this->api_key = $api_key;
        $this->security = $security;

        $this->metadata = [];

        if (is_null($http_client)) {
            $http_client = new Client();
        }

        $this->http_client = $http_client; // CommonMixin
    }

    /**
     * Catchall function for invalid method calls.
     *
     * @throws FilestackException   method not found in allowed lists
     */
    public function __call($method, $args)
    {
        throw new FilestackException("$method() is not a valid method.", 400);
    }

    /**
     * Set this Filelink's transform_url to include ascii task
     *
     * @param string   $background      background color of the HTML file. This
     *                                  can be the word for a color hex value
     *                                  e.g. ('black' or '000000')
     * @param bool     $colored         Reproduces the colors in the original image
     *                                  if set to true.
     * @param string   $foreground      Specifies the font color of ASCII images.
     *                                  Only works in non-colored mode.
     * @param bool     $reverse         Reverses the character set used to generate
     *                                  the ASCII output. Requires colored:true.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function ascii($background = 'white', $colored = false,
        $foreground='red', $reverse = false, $size = 100)
    {
        $options = [
            'b' => $background,
            'c' => $colored ? 'true' : 'false',
            'f' => $foreground,
            'r' => $reverse ? 'true' : 'false',
            's' => $size
        ];

        // call TransformationMixin function
        $this->setTransformUrl('ascii', $options);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include blackwhite task
     *
     * @param int   $threshold      Controls the balance between black and white
     *                              (contrast) in the returned image. This parameter
     *                              accepts integers between 1 and 100. The lower
     *                              the number the less black appears in the picture.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function blackWhite($threshold = 50)
    {
        // call TransformationMixin function
        $this->setTransformUrl('blackwhite', ['t' => $threshold]);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include blur task
     *
     * @param int   $amount     The amount to blur the image. The value for this
     *                          parameter can be any integer in a range from 1 to 20.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function blur($amount = 2)
    {
        // call TransformationMixin function
        $this->setTransformUrl('blur', ['a' => $amount]);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include border task
     *
     * @param string    $background     Sets the background color to display behind
     *                                  the image. This can be the word for a color,
     *                                  or the hex color code, e.g. ('red' or 'FF0000')
     * @param string    $color          Sets the color of the border to render around
     *                                  the image. This can be the word for a color,
     *                                  or the hex color code, e.g. ('red' or 'FF0000')
     * @param int       $width          Sets the width in pixels of the border to render
     *                                  around the image. The value for this parameter
     *                                  must be in a range from 1 to 1000.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function border($background = 'white', $color = 'black', $width=2)
    {
        $options = [
            'b' => $background,
            'c' => $color,
            'w' => $width
        ];

        // call TransformationMixin function
        $this->setTransformUrl('border', $options);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the circle task
     *
     * @param string    $background     Sets the background color to display behind
     *                                  the image. This can be the word for a color,
     *                                  or the hex color code, e.g. ('red' or 'FF0000')
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function circle($background = 'black')
    {
        // call TransformationMixin function
        $this->setTransformUrl('circle', ['b' => $background]);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the collage task
     *
     * @param array     $files          An array of Filestack file handles or external
     *                                  urls. These are the images that will comprise
     *                                  the other images in the collage. The order in
     *                                  which they appear in the array dictates how the
     *                                  images will be arranged.
     * @param int       $width          width of result image (1 to 10000)
     * @param int       $height         height of result image (1 to 10000)
     * @param string    $color          Border color for the collage. This can be a
     *                                  word or hex value, e.g. ('red' or 'FF0000')
     * @param string    $fit            auto or crop.  Allows you to control how the
     *                                  images in the collage are manipulated so that
     *                                  the final collage image will match the height
     *                                  and width parameters you set more closely.
     *                                  Crop will produce a result that is closest
     *                                  to the height and width parameters you set.
     * @param int       $margin         Sets the size of the border between and around
     *                                  the images.  Range is 1 to 100.
     * @param bool      $auto_rotate    Setting this parameter to true automatically
     *                                  rotates all the images in the collage
     *                                  according to their exif orientation data.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function collage($files, $width, $height,
        $color = 'white', $fit = 'auto', $margin = 10, $auto_rotate = false)
    {
        $options = [
            'f' => json_encode($files),
            'w' => $width,
            'h' => $height,
            'c' => $color,
            'i' => $fit,
            'm' => $margin,
            'a' => $auto_rotate
        ];

        // call TransformationMixin function
        $this->setTransformUrl('collage', $options);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the compress task
     *
     * @param bool    $metadata     By default the compress task will strip photo
     *                              metadata out of the image to reduce the file
     *                              size. If you need to maintain the file metadata,
     *                              you can set this to true in order to prevent
     *                              the metadata from being removed.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function compress($metadata = false)
    {
        // call TransformationMixin function
        $this->setTransformUrl('compress', ['m' => $metadata]);

        return $this;
    }

    /**
     * Convert this filelink to another audio format.  You can setup webhooks
     * to notify you when the transcoding has completed. See our online documentation
     * for more details:
     * https://www.filestack.com/docs/audio-transformations
     *
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
    public function convertAudio($format, $options = [], $force = false)
    {
        $transform_tasks = [
            'video_convert' => $options
        ];

        // set filetype
        $transform_tasks['video_convert']['p'] = $format;

        // call TransformationMixin function
        $result = $this->sendVideoConvert($this->handle, $transform_tasks,
            $this->security, $force);

        return $result;
    }

    /**
     * Convert this filelink to another file format.  To see which formats
     * can be converted, see:
     * https://www.filestack.com/docs/image-transformations/conversion
     *
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
     *                                JPEGtran and OptiPNG. Value for this parameter
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
    public function convertFile($filetype, $options = [])
    {
        $transform_tasks = [
            'output' => $options
        ];

        // set filetype
        $transform_tasks['output']['f'] = $filetype;

        // call TransformationMixin function
        $result = $this->sendTransform($this->handle, $transform_tasks, $this->security);

        return $result;
    }

    /**
     * Convert this filelink to another video format.
     * You can setup webhooks to notify you when the transcoding has completed.
     * See our online documentation for more details:
     * https://www.filestack.com/docs/video-transformations
     *
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
    public function convertVideo($format, $options = [], $force = false)
    {
        $transform_tasks = [
            'video_convert' => $options
        ];

        // set filetype
        $transform_tasks['video_convert']['p'] = $format;

        // call TransformationMixin function
        $result = $this->sendVideoConvert($this->handle, $transform_tasks, $this->security, $force);

        return $result;
    }

    /**
     * Set this Filelink's transform_url to include crop task
     *
     * @param int   $x_coordinate       x coordinate to start cropping
     * @param int   $y_coordinate       y coordinate to start cropping
     * @param int   $width              width of crop area
     * @param int   $height             height of corp area
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function crop($x_coordinate, $y_coordinate, $width, $height)
    {
        // call TransformationMixin function
        $this->setTransformUrl('crop', ["d" => "[$x_coordinate,$y_coordinate,$width,$height]"]);
        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the detect_faces task
     *
     * @throws FilestackException   if API call fails
     *
     * @return json object
     */
    public function debug()
    {
        // call TransformationMixin functions
        $json_response = $this->sendDebug($this->transform_url,
            $this->api_key, $this->security);

        return $json_response;
    }

    /**
     * Set this Filelink's transform_url to include the detect_faces task
     *
     * @param string    $color          Will change the color of the "face object"
     *                                  boxes and text.  This can be the word for a color,
     *                                  or the hex color code, e.g. ('red' or 'FF0000')
     * @param bool      $export         Set to true to export all face objects to a JSON.
     * @param float     $minSize        Size as a decimal number to weed out objects
     *                                  that most likely are not faces. This can be an
     *                                  integer or a float in a range from 0.01 to 10000.
     * @param float     $maxSize        Size as a decimal number to weed out objects
     *                                  that most likely are not faces. This can be an
     *                                  integer or a float in a range from 0.01 to 10000.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function detectFaces($color='dimgray', $export = false,
                                $min_size = 0.35, $max_size = 0.35)
    {
        $options = [
            'c' => $color,
            'e' => $export ? 'true' : 'false',
            'n' => $min_size,
            'x' => $max_size
        ];

        // call TransformationMixin function
        $this->setTransformUrl('detect_faces', $options);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the enhance task
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function enhance()
    {
        // call TransformationMixin function
        $this->setTransformUrl('enhance');

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the modulate task
     *
     * @param int       $brightness     The amount to change the brightness of an
     *                                  image. The value range is 0 to 10000.
     * @param int       $hue            The degree to set the hue to. The value
     *                                  range is 0 - 359, where 0 is the equivalent
     *                                  of red and 180 is the equivalent of cyan.
     * @param int       $saturation     The amount to change the saturation of image.
     *                                  The value range is 0 to 10000.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function modulate($brightness = 100, $hue = 0, $saturation = 100)
    {
        $options = [
            'b' => $brightness,
            'h' => $hue,
            's' => $saturation
        ];

        // call TransformationMixin function
        $this->setTransformUrl('modulate', $options);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the monochrome task
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function monochrome()
    {
        // call TransformationMixin function
        $this->setTransformUrl('monochrome');

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the negative task
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function negative()
    {
        // call TransformationMixin function
        $this->setTransformUrl('negative');

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the oil_paint task
     *
     * @param int       $amount     The amount to transform the image with the oil
     *                              paint filter. The value range is 1 to 10.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function oilPaint($amount = 2)
    {
        // call TransformationMixin function
        $this->setTransformUrl('oil_paint', ['a' => $amount]);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the partial_blur task
     *
     * @param int       $amount     The amount to blur the image. Range is 1 to 20
     * @param int       $blur       The amount to blur the image. Range is 0 to 20.
     * @param array     $objects    The area(s) of the image to blur. This variable
     *                              is an array of arrays. Each array input for
     *                              this parameter defines a different section of
     *                              the image and must have exactly 4 integers:
     *                              'x coordinate,y coordinate,width,height' - e.g.
     *                              [[10,20,200,250]] selects a 200x250px rectangle
     *                              starting 10 pixels from the left edge of the
     *                              image and 20 pixels from the top edge of the
     *                              image. The values for these arrays can be any
     *                              integer from 0 to 10000.
     * @param string    $type       The shape of the blur area. The options are rect
     *                              (for a rectangle shape) or oval (for an oval shape).
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function partialBlur($amount = 10, $blur = 4, $objects = [], $type = 'rect')
    {
        $options = [
            'a' => $amount,
            'l' => $blur,
            'o' => $objects,
            't' => $type
        ];

        // call TransformationMixin function
        $this->setTransformUrl('partial_blur', $options);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the partial_pixelate task
     *
     * @param int       $amount     Amount to pixelate the image. Range is 2 to 100
     * @param int       $blur       Amount to pixelate the image. Range is 0 to 20.
     * @param array     $objects    Area(s) of the image to blur. This variable
     *                              is an array of arrays. Each array input for
     *                              this parameter defines a different section of
     *                              the image and must have exactly 4 integers:
     *                              'x coordinate,y coordinate,width,height' - e.g.
     *                              [[10,20,200,250]] selects a 200x250px rectangle
     *                              starting 10 pixels from the left edge of the
     *                              image and 20 pixels from the top edge of the
     *                              image. The values for these arrays can be any
     *                              integer from 0 to 10000.
     * @param string    $type       The shape of the blur area. The options are rect
     *                              (for rectangle shape) or oval (for oval shape).
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function partialPixelate($amount = 10, $blur = 4, $objects = [], $type = 'rect')
    {

        // call TransformationMixin function
        $this->setTransformUrl('partial_pixelate', ['a' => $amount,
            'l' => $blur, 'o' => $objects, 't' => $type]);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the pixelate task
     *
     * @param int       $amount     Amount to transform the image with pixelate
     *                              filter. The value range is 2 to 100.
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function pixelate($amount = 2)
    {
        // call TransformationMixin function
        $this->setTransformUrl('pixelate', ['a' => $amount]);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the polaroid task
     *
     * @param string    $background     Sets the background color to display behind
     *                                  the image. This can be the word for a color,
     *                                  or the hex color code, ('red' or 'FF0000')
     * @param string    $color          Sets the polaroid frame color. This can be
     *                                  a word or the hex value ('red' or 'FF0000')
     * @param int       $rotate         Degree by which to rotate the image clockwise.
     *                                  Range is 0 to 359.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function polaroid($background = 'white', $color = 'snow', $rotate = 45)
    {
        $options = [
            'b' => $background,
            'c' => $color,
            'r' => $rotate
        ];

        // call TransformationMixin function
        $this->setTransformUrl('polaroid', $options);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the quality task
     *
     * @param int       $value      This task will take a JPG or WEBP file and
     *                              reduce the file size of the image by reducing
     *                              the quality. If  file is not a JPG, the original
     *                              file will be returned. If after the conversion,
     *                              the resulting file is not smaller than the original,
     *                              the original file will be returned. Using quality
     *                              as a seperate task is better when no previous
     *                              image manipulations (no previous recompressions)
     *                              have been done. (1 to 100)
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function quality($value)
    {
        // call TransformationMixin function
        $this->setTransformUrl('quality', ['v' => $value]);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the redeye task
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function redEye()
    {
        // call TransformationMixin function
        $this->setTransformUrl('redeye');

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the resize task
     *
     * @param int       $width      The width in pixels to resize the image to.
     *                              The range is 1 to 10000.
     * @param int       $height     The height in pixels to resize the image to.
     *                              The range is 1 to 10000.
     * @param string    $fit        clip, crop, scale, or max
     *                                'clip': Resizes the image to fit within the
     *                                  specified parameters without distorting,
     *                                  cropping, or changing the aspect ratio.
     *                                'crop': Resizes the image to fit the specified
     *                                  parameters exactly by removing any parts of
     *                                  the image that don't fit within the boundaries.
     *                                'scale': Resizes the image to fit the specified
     *                                  parameters exactly by scaling the image to
     *                                  the desired size. The aspect ratio of the
     *                                  image is not respected and the image can
     *                                  be distorted using this method.
     *                                'max': Resizes the image to fit within the
     *                                  parameters, but as opposed to 'clip' will
     *                                  not scale the image if the image is smaller
     *                                  than the output size.
     * @param string    $align      Using align, you can choose the area of the image
     *                              to focus on. Possible values:
     *                              center, top, bottom, left, right, or faces
     *                              You can also specify pairs e.g. align:[top,left].
     *                              Center cannot be used in pairs.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function resize($width, $height, $fit = 'clip', $align = 'center')
    {
        $options = [
            'w' => $width,
            'h' => $height,
            'f' => $fit,
            'a' => $align
        ];

        // call TransformationMixin function
        $this->setTransformUrl('resize', $options);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the rounded_corners task
     *
     * @param string    $background     Sets the background color to display behind
     *                                  the image. This can be the word for a color,
     *                                  or the hex color code, e.g. ('red' or 'FF0000')
     * @param float     $blur           Specify the amount of blur to apply to the
     *                                  rounded edges of the image. (0 - 20).
     * @param int       $radius         The radius of the rounded corner effect on
     *                                  the image. (0-10000)
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function roundedCorners($background = 'white', $blur = 0.3, $radius = 10)
    {
        $options = [
            'b' => $background,
            'l' => $blur,
            'r' => $radius
        ];

        // call TransformationMixin function
        $this->setTransformUrl('rounded_corners', $options);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the rotate task
     *
     * @param string    $background     Sets the background color to display behind
     *                                  the image. This can be the word for a color,
     *                                  or the hex color code, e.g. ('red' or 'FF0000')
     * @param int       $deg            The degree by which to rotate the image
     *                                  clockwise (0 to 359). Alternatively, you can
     *                                  set the degree to 'exif' and the image will
     *                                  be rotated based upon any exif metadata it
     *                                  may contain.
     * @param bool      $exif           Sets the EXIF orientation of the image to
     *                                  EXIF orientation 1. The exif=false parameter
     *                                  takes an image and sets the exif orientation
     *                                  to the first of the eight EXIF orientations.
     *                                  The image will behave as though it is contained
     *                                  in an html img tag if displayed in application
     *                                  that supports EXIF orientations.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function rotate($background = 'white', $deg = 0, $exif = false)
    {
        $options = [
            'b' => $background,
            'd' => $deg,
            'e' => $exif ? 'true' : 'false'
        ];

        // call TransformationMixin function
        $this->setTransformUrl('rotate', $options);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the sepia task
     *
     * @param int       $tone      The value to set the sepia tone to. The value
     *                             should be 0 to 100.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function sepia($tone = 80)
    {
        // call TransformationMixin function
        $this->setTransformUrl('sepia', ['t' => $tone]);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the sharpen task
     *
     * @param int       $amount    The amount to sharpen the image. The value
     *                             should be 0 to 20.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function sharpen($amount = 2)
    {
        // call TransformationMixin function
        $this->setTransformUrl('sharpen', ['a' => $amount]);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the shadow task
     *
     * @param string    $background     Sets the background color to display behind
     *                                  the image. This can be the word for a color,
     *                                  or the hex color code, e.g. ('red' or 'FF0000')
     * @param int       $blur           Sets the level of blur for the shadow effect.
     *                                  Value range is 0 to 20.
     * @param int       $opacity        Sets the opacity level of the shadow effect.
     *                                  Value range is 0 to 100.
     * @param array     $vector         Sets the vector of the shadow effect. The
     *                                  value must be an array of two integers in a
     *                                  range from -1000 to 1000. These are the
     *                                  X and Y parameters that determine the position
     *                                  of the shadow.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function shadow($background = 'white', $blur = 4,
                           $opacity = 60, $vector = [4,4])
    {
        $options = [
            'b' => $background,
            'l' => $blur,
            'o' => $opacity,
            'v' => $vector
        ];

        // call TransformationMixin function
        $this->setTransformUrl('shadow', $options);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the torn_edges task
     *
     * @param string    $background     Sets the background color to display behind
     *                                  the image. This can be the word for a color,
     *                                  or the hex color code, e.g. ('red' or 'FF0000')
     * @param array     $spread         Sets the spread of the tearing effect. The
     *                                  value must be an array of two integers in
     *                                  a range from 1 to 10000.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function tornEdges($background = 'white', $spread = [1,10])
    {
        $options = [
            'b' => $background,
            's' => $spread
        ];

        // call TransformationMixin function
        $this->setTransformUrl('torn_edges', $options);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the upscale task
     *
     * @param string    $noise          none, low, medium or high
     *                                  Setting to reduce the level of noise in
     *                                  an image. This noise reduction is performed
     *                                  algorithmically and the aggressiveness of
     *                                  the noise reduction is determined by low,
     *                                  medium and high gradations.
     * @param string    $style          artwork or photo
     *                                  If the image being upscaled is a drawing
     *                                  or piece of artwork with smooth lines, you
     *                                  will receive better results from the upscaling
     *                                  process if you also include the artwork
     *                                  style parameter.
     * @param bool      $upscale        True will generate an image that is 2x the
     *                                  dimensions of the original. If false is
     *                                  passed as part of the task, then features
     *                                  like noise reduction can be used without
     *                                  changing the resolution of the image.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function upscale($noise = 'none', $style = 'photo', $upscale = true)
    {
        $options = [
            'n' => $noise,
            's' => $style,
            'u' => $upscale ? 'true' : 'false'
        ];

        // call TransformationMixin function
        $this->setTransformUrl('upscale', $options);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the vignette task
     *
     * @param int       $amount         The opacity of the vignette effect (0-100)
     * @param string    $background     Sets the background color to display behind
     *                                  the image. This can be the word for a color,
     *                                  or the hex color code, e.g. ('red' or 'FF0000')
     * @param string    $blurmode       linear or gaussian
     *                                  Controls the type of blur applied to the
     *                                  vignette - linear or gaussian. The vignette
     *                                  effect uses gaussian blur by default because
     *                                  it produces a more defined vignette around
     *                                  the image. Specifying linear is faster,
     *                                  but produces a less-defined blur effect,
     *                                  even at higher amounts.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function vignette($amount = 20, $background = 'white', $blurmode = 'gaussian')
    {
        $options = [
            'a' => $amount,
            'b' => $background,
            'm' => $blurmode
        ];

        // call TransformationMixin function
        $this->setTransformUrl('vignette', $options);

        return $this;
    }

    /**
     * Set this Filelink's transform_url to include the watermark task
     *
     * @param string    $file_handle    The Filestack handle of the image that
     *                                  you want to layer on top of another
     *                                  image as a watermark.
     * @param string    $position       top, middle, bottom, left, center, or right
     *                                  The position of the overlayed image. These
     *                                  values can be paired as well like position:
     *                                  [top,right].
     * @param int       $size           The size of the overlayed image as a percentage
     *                                  of its original size. The value must be an
     *                                  integer between 1 and 500.
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function watermark($file_handle, $position = 'center', $size = 100)
    {
        $options = [
            'f' => $file_handle,
            'p' => $position,
            's' => $size
        ];

        // call TransformationMixin function
        $this->setTransformUrl('watermark', $options);

        return $this;
    }

    /**
     * Get the content of filelink
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return string (file content)
     */
    public function getContent()
    {
        // call CommonMixin function
        $result = $this->sendGetContent($this->url(), $this->security);
        return $result;
    }

    /**
     * Get metadata of filehandle
     *
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
    public function getMetaData($fields = [])
    {
        // call CommonMixin function
        $result = $this->sendGetMetaData($this->url(), $fields, $this->security);

        foreach ($result as $key => $value) {
            $this->metadata[$key] = $value;
        }

        return $result;
    }

    /**
     * Delete this filelink from cloud storage
     *
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return bool (true = delete success, false = failed)
     */
    public function delete()
    {
        // call CommonMixin function
        $result = $this->sendDelete($this->handle, $this->api_key, $this->security);
        return $result;
    }

    /**
     * Download filelink as a file, saving it to specified destination
     *
     * @param string    $destination        destination filepath to save to, can
     *                                      be folder name (defaults to stored filename)
     *
     * @throws FilestackException   if API call fails
     *
     * @return bool (true = download success, false = failed)
     */
    public function download($destination)
    {
        // call CommonMixin function
        $result = $this->sendDownload($this->url(), $destination, $this->security);
        return $result;
    }

    /**
     * Overwrite this filelink in cloud storage
     *
     * @param string            $filepath   real path to file
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return boolean
     */
    public function overwrite($filepath)
    {
        $result = $this->sendOverwrite($filepath,
            $this->handle, $this->api_key, $this->security);

        // update metadata
        $this->metadata['filename'] = $result->metadata['filename'];
        $this->metadata['mimetype'] = $result->metadata['mimetype'];
        $this->metadata['size'] = $result->metadata['size'];

        return true;
    }

    /**
     * Reset the transformation url of this Filelink.  Call this function if
     * you are calling multiple transformations on the same filelink without
     * using the transform method.
     */
    public function resetTransform()
    {
        $this->transform_url = null;
    }

    /**
     * Save this transformed filelink in cloud storage
     *
     * @param array     $options    array of store options
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack\Filelink
     */
    public function save($options = [])
    {
        $this->initTransformUrl();
        $this->transform_url = $this->insertTransformStr($this->transform_url,
            'store', $options);

        // call CommonMixin function
        $response = $this->sendRequest('GET', $this->transform_url);
        $filelink = $this->handleResponseCreateFilelink($response);

        return $filelink;
    }

    /**
     * Append or Create a task to the transformation url for this filelink
     *
     * @param array $options    task options, e.g. ['b' => '00FF00', 'd' => '45']
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return void
     */
    public function setTransformUrl($method, $options = [])
    {
        $this->initTransformUrl();
        $this->transform_url = $this->insertTransformStr($this->transform_url,
            $method, $options);
    }

    /**
     * Get the sfw (safe for work) flag for this filelink
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return json
     */
    public function getSafeForWork()
    {
        $result = $this->sendGetSafeForWork($this->handle, $this->security);
        return $result;
    }

    /**
     * Get the tags for this filelink
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return json
     */
    public function getTags()
    {
        $result = $this->sendGetTags($this->handle, $this->security);
        return $result;
    }

    /**
     * Applied array of transformation tasks to this file link.
     *
     * @param array     $transform_tasks    array of transformation tasks and
     *                                      optional attributes per task
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack\Filelink or contents
     */
    public function transform($transform_tasks)
    {
        // call TransformationMixin
        $result = $this->sendTransform($this->handle,
                            $transform_tasks, $this->security);

        return $result;
    }

    /**
     * Set this Filelink's transform_url to include the zip task
     *
     * @throws FilestackException   if API call fails, e.g 404 file not found
     *
     * @return Filestack/Filelink
     */
    public function zip()
    {
        // call TransformationMixin function
        $this->setTransformUrl('zip');

        return $this;
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
     * @param FilestackSecurity    $security   Filestack security object
     *
     * @return string
     */
    public function signedUrl($security)
    {
        return sprintf('%s?policy=%s&signature=%s',
            $this->url(),
            $security->policy,
            $security->signature);
    }

    /**
     * Initialize transform url if it doesnt exist
     */
    protected function initTransformUrl()
    {
        if (!$this->transform_url) {
            // security in a different format for transformations
            $security_str = $this->security ?
                sprintf('/security=policy:%s,signature:%s',
                    $this->security->policy,
                    $this->security->signature
                ) : '';

            $this->transform_url = sprintf(FilestackConfig::CDN_URL . '%s/%s',
                $security_str,
                $this->handle);
        }
    }
}
