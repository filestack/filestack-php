<?php
namespace Filestack;

use Filestack\FilestackException;

/**
 * Class representing a Filestack Security object.  Use this clas
 * if you have security enabled on your apikey.
 */
class FilestackSecurity
{
    const DEFAULT_EXPIRY = 3600;

    protected $allowed_options;
    protected $secret;

    public $policy;
    public $signature;
    public $options;

    /**
     * Filestack Security constructor
     *
     * @param string    $secret     security secret, set this in
     *                              your dev portal
     * @param int       $expiry     expiration time in seconds,
     *                              default is 1 hour
     * @param array     $options    additional options you can send such as:
     *                              call: The calls that you allow this policy to
     *                                  make, e.g: convert, exif, pick, read, remove,
     *                                  stat, store, write, writeUrl
     *                              container: (regex) store must match container
     *                                  that the files will be stored under
     *                              expiry: (timestamp) epoch_timestamp expire,
     *                                  defaults to 1hr
     *                              handle: specific file this policy can access
     *                              maxSize: (number) maximum file size in bytes
     *                                  that can be stored by requests with policy
     *                              minSize: (number) minimum file size in bytes
     *                                  that can be stored by requests with policy
     *                              path: (regex) store must match the path that
     *                                  the files will be stored under.
     *                              url: (regex) subset of external URL domains
     *                                  that are allowed to be image/document
     *                                  sources for processing
     */
    public function __construct($secret, $options = [])
    {
        $this->allowed_options = [
            'call', 'container', 'expiry', 'handle',
            'maxSize', 'minSize', 'path', 'url'
        ];

        $this->secret = $secret;
        $this->options = $options;

        // set expiry time if one wasn't passed in
        if (!array_key_exists('expiry', $options)) {
            $expiry_timestamp = time() + self::DEFAULT_EXPIRY;
            $options['expiry'] = $expiry_timestamp;
        }

        $result = $this->generate($secret, $options);

        $this->policy = $result['policy'];
        $this->signature = $result['signature'];
    }

    /**
     * generate a security policy and signature
     *
     * @param string    $secret     random hash secret
     * @param array     $options    array of policy options
     *
     * @throws FilestackException   e.g. policy option not allowed
     *
     * @return array ['policy'=>'encrypted_value', 'signature'=>'encrypted_value']
     */
    private function generate($secret, $options = [])
    {
        if (!$secret) {
            throw new FilestackException("Secret can not be empty", 400);
        }

        // check that options passed in are valid (on allowed list)
        $this->validateOptions($options);

        // set encoded values to policy and signature
        $policy = json_encode($options);
        $policy = $this->urlsafeB64encode($policy);
        $signature = $this->createSignature($policy, $secret);

        return ['policy' => $policy, 'signature' => $signature];
    }

    /**
     * Append policy and signature to url
     *
     * @param string    $url     the url to sign
     *
     * @return string (url with policy and signature appended)
     */
    public function signUrl($url)
    {
        if (strrpos($url, '?') === false) {
            // append ? if one doesn't exist
            $url .= '?';
        }
        return sprintf('%s&policy=%s&signature=%s',
            $url, $this->policy, $this->signature);
    }

    /**
     * Verify that a policy is valid
     *
     * @param array     $policy                 policy to verify
     * @param string?   $secret                 security secret
     *
     * @return bool
     */
    public function verify($policy, $secret)
    {
        try {
            $result = $this->generate($secret, $policy);
        } catch (FilestackException $e) {
            return false;
        }

        return $result;
    }

    /**
     * Generate a signature
     *
     * @param   string   $encoded_policy    url safe base64encoded string
     * @param   string   $secret            your secret
     *
     * @return string (sha256 hashed)
     */
    protected function createSignature($encoded_policy, $secret)
    {
        $signature = hash_hmac('sha256', $encoded_policy, $secret);
        return $signature;
    }

    /**
     * Validate options are allowed
     *
     * @param   array   $options    array of options
     *
     * @throws FilestackException   e.g. policy option not allowed
     *
     * @return bool
     */
    protected function validateOptions($options)
    {
        foreach ($options as $key => $value) {
            if (!in_array($key, $this->allowed_options)) {
                throw new FilestackException("Invalid policy option: $key:$value", 400);
            }
        }

        return true;
    }

    /**
     * Helper functions
     */
    protected function urlsafeB64encode($string)
    {
        $data = base64_encode($string);
        $data = str_replace(array('+','/'), array('-','_'), $data);
        return $data;
    }
}
