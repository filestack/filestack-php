<?php
namespace Filestack\Tests;

use Filestack\FilestackConfig;
use Filestack\FilestackSecurity;

class BaseTest extends \PHPUnit_Framework_TestCase
{
    protected $test_api_key;
    protected $test_secret;
    protected $test_file_path;
    protected $test_file_url;
    protected $test_file_handle;
    protected $test_security;

    protected $mock_response_json;

    public function __construct()
    {
        $this->test_api_key = 'YOUR_FILESTACK_API_KEY';
        $this->test_file_handle = 'zOHgdRG4S5WikRbZNBEn';

        $this->test_api_key_no_sec = 'YOUR_FILESTACK_API_KEY';
        $this->test_file_handle_no_sec = 'SD20cycaQwMttDxaj4YK';

        $this->test_file_url = FilestackConfig::CDN_URL . '/' . $this->test_file_handle;
        $this->test_secret = 'YOUR_FILESTACK_SECURITY_SECRET';
        $this->test_filepath = __DIR__ . '/testfiles/calvinandhobbes.jpg';
        $this->test_security = new FilestackSecurity($this->test_secret);

        $this->mock_response_json = json_encode([
            'filename'  => 'somefilename.jpg',
            'size'      => '1000',
            'type'      => 'image/jpg',
            'url'       => 'https://cdn.filestack.com/somefilehandle'
        ]);
    }

    public function testFileExists()
    {
        $testfile = fopen($this->test_filepath, 'r');
        $this->assertNotNull($testfile);
    }
}
