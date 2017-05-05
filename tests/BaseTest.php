<?php
namespace Filestack\Test;

use Filestack\FilestackSecurity;

class BaseTest extends \PHPUnit_Framework_TestCase
{
    protected $test_api_key;
    protected $test_secret;
    protected $test_file_path;
    protected $test_file_url;
    protected $test_file_handle;
    protected $test_security;

    public function __construct()
    {
        $this->test_api_key = 'A5lEN6zU8SemSBWiwcGJhz';
        $this->test_secret = '3UAQ64UWMNCCRF36CY2NSRSPSU';

        $this->test_filepath = __DIR__ . '/testfiles/calvinandhobbes.jpg';
        $this->test_file_url = 'https://cdn.filestackcontent.com/IIkUk9D8TWKHldxmMVRt';
        $this->test_file_handle = 'IIkUk9D8TWKHldxmMVRt';
        $this->test_security = new FilestackSecurity($this->test_secret);
    }

    public function testFileExists()
    {
        $testfile = fopen($this->test_filepath, 'r');
        $this->assertNotNull($testfile);
    }
}
