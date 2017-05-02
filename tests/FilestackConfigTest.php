<?php

use Filestack\FilestackConfig;
use Filestack\FilestackException;

class FilestackConfigTest extends \PHPUnit_Framework_TestCase
{
    protected $test_api_key;

    protected function setUp()
    {
        // setup
        $this->test_api_key = 'A5lEN6zU8SemSBWiwcGJhz';
    }

    public function tearDown()
    {
        // teardown calls
    }

    /*
     * Test building storeUrl
     */
    public function testBuildStoreUrl()
    {
        $security = null;
        $options = [
            'Location' => 'dropbox',
            'Filename' => 'somefilename.jpg',
        ];

        $expected_url = sprintf('%s/store/%s?key=%s&filename=%s',
            FilestackConfig::API_URL,
            $options['Location'],
            $this->test_api_key,
            $options['Filename']);

        $url = FilestackConfig::createUrl('store', $this->test_api_key, $options, $security);

        $this->assertEquals($url, $expected_url);
    }
}