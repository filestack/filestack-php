<?php
namespace Filestack\Tests;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * A mock Http response with getStatusCode and getBody functions.
 */
class MockHttpResponse implements ResponseInterface
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

    public function getProtocolVersion()
    {
    }

    public function withProtocolVersion($version)
    {
    }

    public function hasHeader($name)
    {
    }

    public function getHeaderLine($name)
    {
    }

    public function withHeader($name, $value)
    {
    }

    public function withAddedHeader($name, $value)
    {
    }

    public function withoutHeader($name)
    {
    }

    public function withBody(StreamInterface $body)
    {
    }

    public function withStatus($code, $reasonPhrase = '')
    {
    }

    public function getReasonPhrase()
    {
    }
}
