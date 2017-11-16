<?php
namespace Filestack\Tests;

use Filestack\FilestackClient;
use Filestack\FilestackSecurity;
use Filestack\FilestackException;

class FilestackSecurityTest extends BaseTest
{
    /*
     * Test security intialization with valid options
     */
    public function testSecurityInitSuccess()
    {
        $options =  [
            'expiry'    => 11235813,
            'call'      => ['read', 'write', 'store'],
            'container' => 'some-container',
            'handle'    => 'some-file-handle',
            'maxSize'   => 1024,
            'minSize'   => 100,
            'path'      => '/some/example/path',
            'url'       => 'http://someurl.com'
        ];
        $security = new FilestackSecurity($this->test_secret, $options);

        $this->assertNotNull($security->policy);
        $this->assertNotNull($security->signature);
    }

    /*
     * Test passing in invalid security options throws exception
     */
    public function testSecurityInvalidOptions()
    {
        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $options = ['some-invalid-option' => 'some-value'];
        $security = new FilestackSecurity($this->test_secret, $options);
        $this->assertNull($security);
    }

    /*
     * Test downloading a Filestack File with security
     */
    public function testSecurityDownload()
    {
        $mock_response = new MockHttpResponse(
            200,
            new MockHttpResponseBody('some content')
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $destination = __DIR__ . '/testfiles/my-custom-filename.jpg';

        $options =  [
            'call'      => ['read', 'write', 'store'],
            'handle'    => 'IIkUk9D8TWKHldxmMVRt',
            'maxSize'   => 1024,
            'minSize'   => 100
        ];
        $security = new FilestackSecurity($this->test_secret, $options);
        $client = new FilestackClient(
            $this->test_api_key,
            $security,
            $stub_http_client
        );
        $result = $client->download($this->test_file_url, $destination);

        $this->assertTrue($result);
    }

    /*
     * Test downloading a Filestack File with security
     */
    public function testDownloadFailedNoCreds()
    {
        $mock_response = new MockHttpResponse(
            403,
            'policy error: proper credentials were not provided'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $destination = __DIR__ . '/testfiles/my-custom-filename.jpg';

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(403);

        $client = new FilestackClient(
            $this->test_api_key,
            null,
            $stub_http_client
        );
        $client->download($this->test_file_url, $destination);
    }

    /*
     * Test downloading a Filestack File with security
     */
    public function testDownloadFailedInvalidCreds()
    {
        $mock_response = new MockHttpResponse(
            403,
            'policy error: proper credentials were not provided'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $destination = __DIR__ . '/testfiles/my-custom-filename.jpg';

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(403);

        $security = new FilestackSecurity('some-invalid-secret-test');
        $client = new FilestackClient(
            $this->test_api_key,
            $security,
            $stub_http_client
        );
        $client->download($this->test_file_url, $destination);
    }

    /*
     * Test verifying a policy and signature
     */
    /*
     * Test security intialization with valid options
     */
    public function testSecurityVerify()
    {
        $options =  [
            'expiry'    => time() + 600,
            'handle'    => 'some-file-handle'
        ];

        $security = new FilestackSecurity($this->test_secret, $options);
        $result = $security->verify($options, $this->test_secret);

        $this->assertNotNull($result);
    }

    /*
     * Test verifying a policy and signature failed
     */
    /*
     * Test security intialization with valid options
     */
    public function testSecurityVerifyFailed()
    {
        $secret = "";
        $options = [];

        $security = new FilestackSecurity($this->test_secret, $options);
        $result = $security->verify($options, $secret);

        $this->assertFalse($result);
    }
}
