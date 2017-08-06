<?php
namespace Filestack\Tests;

use Filestack\HttpStatusCodes;

class HttpStatusCodeTest extends BaseTest
{
    /**
     * Test getMessage
     */
    public function testGetMessage()
    {
        $message = HttpStatusCodes::getMessage(200);
        $this->assertEquals($message, "200 OK");
    }

    /**
     * Test isError
     */
    public function testIsError()
    {
        $result = HttpStatusCodes::isError(400);
        $this->assertTrue($result);
    }

    /**
     * Test isTimeout
     */
    public function testIsTimeoutError()
    {
        $result = HttpStatusCodes::isNetworkError(408);
        $this->assertTrue($result);

        $result2 = HttpStatusCodes::isNetworkError(503);
        $this->assertTrue($result2);

        $result3 = HttpStatusCodes::isNetworkError(504);
        $this->assertTrue($result3);
    }

    /**
     * Test isServerError
     */
    public function testIsServerError()
    {
        $result = HttpStatusCodes::isServerError(500);
        $this->assertTrue($result);
    }
}