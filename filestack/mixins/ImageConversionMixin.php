<?php
namespace Filestack\Mixins;

use Filestack\Transformation;

trait ImageConversionMixin
{
    /**
     * Resize an image
     *
     * @param array     $process_attrs  attributes replated to converstion task
     *
     * @return Transformation object
     */
    public function resize($process_attrs)
    {
        $allowed_attrs = ['w', 'width'];
        $tasks = [];
        foreach ($allowed_attrs as $attr) {
            if (in_array($attr, $process_attrs)) {
                $transform_task = sprintf('%s:%s', $attr, $process_attrs[$attr]);
                array_push($tasks, $transform_task);
            }
        }

        if (property_exists($this, 'conversion_tasks')) {
            $this->conversion_tasks->append(
                sprintf('resize=width:%s,height:%s', '123', '123')
            );
            return $this;
        }

        return new Transformation($this->conversion_tasks);
    }

    /**
     * crop an image
     *
     * @param array     $process_attrs  attributes replated to converstion task
     *
     * @return Transformation object
     */
    public function crop($process_attrs)
    {
        // pass
        return new Transformation($this->conversion_tasks);
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
}
