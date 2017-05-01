<?php
namespace Filestack\Mixins;

/**
 * Mixin for common functionalities used by most Filestack objects
 *
 */
trait CommonMixin
{
    /**
     * Download a file given a filestack file handle.
     *
     * @param string            $handle     Filestack file handle
     * @param Filetack\Security $security   Filestack security object if
     *                                      security settings is turned on
     */
    public function download($handle, $security=null)
    {
        # saves to local drive
    }

    /**
     * Get the content of a file.
     *
     * @param string            $handle     Filestack file handle
     * @param Filetack\Security $security   Filestack security object if
     *                                      security settings is turned on
     */
    public function get_content($handle, $security=null)
    {
        # return bytes
    }
}
