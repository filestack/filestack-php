<?php
namespace Filestack\Tests;

use Filestack\FilestackConfig;
use Filestack\Mixins\CommonMixin;

class CommonMixinTest extends \PHPUnit_Framework_TestCase
{

    function testAddDownloadFlagToUrl()
    {
        $mock = $this->getMockForTrait(CommonMixin::class);
        $base_url = FilestackConfig::CDN_URL.'/some-file-handle';

        // without existing query string
        $url = $base_url;
        $expected_url = $base_url.'?dl=true';
        $this->assertEquals($expected_url, $mock->addDownloadFlagToUrl($url));

        // with signed url query string
        $url = $base_url.'?policy=foo&signature=bar';
        $expected_url = $base_url.'?dl=true&policy=foo&signature=bar';
        $this->assertEquals($expected_url, $mock->addDownloadFlagToUrl($url));
    }
}
