<?php
namespace Filestack;

/**
 * Filestack Transformation object.  Use this class
 * to make transformation to images and other resources.
 */
class Transformation
{
    use Filestack\Mixins\CommonMixin, Filestack\Mixins\ImageConversionMixin;

    private $api_key;
    private $conversion_tasks;

    public $handle;
    public $external_url;

    /**
     * Filestack Transformation constructor
     *
     * @param string    $handle             Filestack file handle
     * @param string?   $external_url       optional external URL
     * @param array     $conversion_tasks   array of conversion tasks
     * @param string    $api_key            your Filestack API Key
     */
    function __construct($handle, $external_url=null,
        $conversion_tasks=[], $api_key=null)
    {
        $this->api_key = $api_key;
        $this->handle = $handle;
        $this->external_url = $external_url;
        $this->conversion_tasks = $conversion_tasks;
    }

    /**
     * return the URL (cdn) of this filelink
     *
     * @return string
     */
    public function url()
    {
        # build url based on conversion tasks
        $url = "";

        return $url;
    }
}
