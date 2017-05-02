<?php
/**
 * A mock Http response with getStatusCode and getBody functions.
 */
class MockHttpResponse
{
    public $status_code;
    public $contents;

    function __construct($status_code, $contents='{}')
    {
        $this->status_code = $status_code;
        $this->contents = $contents;
    }

    public function getStatusCode()
    {
        return $this->status_code;
    }

    public function getBody()
    {
        return $this->contents;
    }
}