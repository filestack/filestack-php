<?php
namespace Filestack;

/**
 * Class representing a filestack security object
 */
class Security
{
    private $allowed;

    public $policy;
    public $signature;

    /**
     * Filestack Security constructor
     *
     * @param string    $secret     security secret, set this in
     *                              your dev portal
     * @param int       $expiry     expiration time in seconds,
     *                              default is 1 hour
     */
    function __construct($secret, $expiry=3600)
    {
        $this->allowed = ['max_size', 'handle', 'call'];
        $this->generate($secret, $expiry);
    }

    /**
     * generate a security policy and signature
     *
     * @param string    $secret     random hash secret
     * @param int       $expiry     expiration time in seconds,
     *                              default is 1 hour
     *
     * @return void
     */
    private function generate($secret, $expiry=3600)
    {
        $this->policy = 'asd';
        $this->signature = 'zxc';
    }

    /**
     * verify that a policy is valid
     *
     * @param string    $policy     policy to verify
     * @param string?   $signature  signature of policy
     * @param string?   $secret     security secret
     *
     * @return bool
     */
    public static verify($policy, $signature=null, $secret=null)
    {
        return true;
    }
}
