<?php
namespace Filestack;

use Filestack\FilestackConfig;

/**
 * Class representing a filestack filelink object
 */
class Filelink
{
    use Mixins\CommonMixin;
    use Mixins\ImageConversionMixin;

    public $handle;

    /**
     * Filelink constructor
     *
     * @param string    $handle     Filestack file handle
     */
    function __construct($handle)
    {
        $this->handle = $handle;
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
    public function signed_url($security)
    {
        return sprintf('url?policy=%s&signature=%s',
            $security->policy, $security->signature);
    }
}