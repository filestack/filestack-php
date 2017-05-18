<?php
namespace Filestack\Test;

/**
 * A mock Http response with getStatusCode and getBody functions.
 */
class MockHttpResponse
{
    public $status_code;
    public $content;

    public function __construct($status_code, $content='{}')
    {
        $this->status_code = $status_code;
        $this->content = $content;
    }

    public function getStatusCode()
    {
        return $this->status_code;
    }

    public function getBody()
    {
        return $this->content;
    }
}
