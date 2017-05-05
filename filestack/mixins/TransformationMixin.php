<?php
namespace Filestack\Mixins;

use Filestack\FilestackException;

trait TransformationMixin
{
    public $allowed_attrs;

    public function __construct()
    {
        $this->setAllowedAttrs();
    }

    /**
     * Return the URL portion of crop operation
     *
     * @param string    $taskname       name of task, e.g. 'crop', 'resize', etc.
     * @param array     $process_attrs  attributes replated to this task
     *
     * @throws Filestack\FilestackException
     *
     * @return Transformation object
     */
    public function getTransformStr($taskname, $process_attrs)
    {
        if (!array_key_exists($taskname, $this->allowed_attrs)) {
            throw new FilestackException('Invalid transformation task', 400);
        }

        $this->validateAttributes($taskname, $process_attrs);

        $tranform_str = $taskname;
        if (count($process_attrs) > 0) {
            $tranform_str .= '=';
        }

        // append attributes if exists
        foreach ($process_attrs as $key => $value) {
            $tranform_str .= "$key:$value,";
        }

        // remove last comma
        if (count($process_attrs) > 0) {
            $tranform_str = substr($tranform_str, 0, strlen($tranform_str) - 1);
        }

        return $tranform_str;
    }

    /**
     * Get detailed information back about successful and failed requests.
     * See https://www.filestack.com/docs/image-transformations/debug
     *
     * @return object debug object
     */
    public function debug()
    {
        # should probably return some kind of Debug object
        return null;
    }

    /**
     * crop an image
     *
     * @param string    $taskname   task name, e.g. "resize, crop, etc."
     * @param array     $attrs      attributes  attributes to validate
     *
     * @throws Filestack\FilestackException     if attribute is not on allowed list
     *
     * @return bool
     */
    protected function validateAttributes($taskname, $attrs)
    {
        foreach ($attrs as $key => $value) {
            if (!in_array($key, $this->allowed_attrs[$taskname])) {
                throw new FilestackException(
                    "Invalid transformation attribute $key for $taskname",
                    400
                );
            }
        }

        return true;
    }

    protected function setAllowedAttrs()
    {
        $allowed_attrs = [];

        $this->allowed_attrs['border'] = [
            'b', 'c', 'w',
            'background', 'color', 'width'
        ];

        $this->allowed_attrs['circle'] = [
            'b',
            'background'
        ];

        $this->allowed_attrs['crop'] = [
            'd',
            'dim'
        ];

        $this->allowed_attrs['detect_faces'] = [
            'c', 'e', 'n', 'N',
            'color', 'export', 'minSize', 'maxSize'
        ];

        $this->allowed_attrs['polaroid'] = [
            'b', 'c', 'r',
            'background', 'color', 'rotate'
        ];

        $this->allowed_attrs['resize'] = [
            'w', 'h', 'f', 'a',
            'width', 'height', 'fit', 'align'
        ];

        $this->allowed_attrs['rounded_corners'] = [
            'b', 'l', 'r',
            'background', 'blur', 'radius'
        ];

        $this->allowed_attrs['rotate'] = [
            'b', 'd', 'e',
            'background', 'deg', 'exif'
        ];

        $this->allowed_attrs['shadow'] = [
            'b', 'l', 'o', 'v',
            'background', 'blur', 'opacity', 'vector'
        ];

        $this->allowed_attrs['torn_edges'] = [
            'b', 's',
            'background', 'spread'
        ];

        $this->allowed_attrs['vignette'] = [
            'a', 'b', 'm',
            'amount', 'background', 'blurmode',
        ];

        $this->allowed_attrs['watermark'] = [
            'f', 'p', 's',
            'file', 'position', 'size'
        ];
    }
}
