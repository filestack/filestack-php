<?php
namespace Filestack\Tests;

class MockCommonMixin
{
    use \Filestack\Mixins\CommonMixin;

    public $api_key;
    public $security;

    public function __construct($api_key, $security = null, $http_client = null)
    {
        $this->api_key = $api_key;
        $this->security = $security;
        $this->http_client = $http_client; // CommonMixin
    }
}