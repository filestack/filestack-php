<?php
namespace Filestack\Tests;

/**
 * A mock Http response body
 */
class MockHttpResponseBody
{
    public $content;

    public function __construct($content = '', $headers = [])
    {
        $this->content = $content;
    }

    public function getContents()
    {
        return $this->content;
    }
}
