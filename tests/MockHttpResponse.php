<?php
/**
 * A mock Http response with getStatusCode and getBody functions.
 */
class MockHttpResponse
{
    public $status_code;
    public $content;

    function __construct($status_code, $content='{}')
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

class MockHttpResponseBody
{
    public $content;

    function __construct($content='')
    {
        $this->content = $content;
    }

    public function getContents()
    {
        return $this->content;
    }
}
