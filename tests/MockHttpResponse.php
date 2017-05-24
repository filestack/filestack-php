<?php
namespace Filestack\Tests;

/**
 * A mock Http response with getStatusCode and getBody functions.
 */
class MockHttpResponse
{
    public $status_code;
    public $content;
    public $headers;

    public function __construct($status_code, $content = '{}', $headers = [])
    {
        $this->status_code = $status_code;
        $this->content = $content;
        $this->headers = $headers;
    }

    public function getStatusCode()
    {
        return $this->status_code;
    }

    public function getBody()
    {
        return $this->content;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getHeader($header_name)
    {
        return $this->headers[$header_name];
    }
}
