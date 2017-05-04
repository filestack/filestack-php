<?php
namespace Filestack\Test;

use Filestack\FilestackConfig;
use Filestack\FilestackSecurity;
use Filestack\FilestackException;

class FilestackConfigTest extends BaseTest
{
    /**
     * Test building delete Url
     */
    public function testBuildDeleteUrl()
    {
        $security = new FilestackSecurity('some-secret');
        $options = [
            'handle' => 'somefile-handle'
        ];

        $expected_url = sprintf('%s/file/%s?key=%s&policy=%s&signature=%s',
            FilestackConfig::API_URL,
            $options['handle'],
            $this->test_api_key,
            $security->policy,
            $security->signature);

        $url = FilestackConfig::createUrl('delete', $this->test_api_key, $options, $security);
        $this->assertEquals($url, $expected_url);
    }

    /**
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