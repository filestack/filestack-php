<?php
namespace Filestack;

/**
 * Filestack config constants, such as base URLs
 */
class FilestackConfig
{
    const API_URL = 'https://www.filestackapi.com/api';
    const PROCESSING_URL = 'https://cdn.filestackcontent.com';
    const CDN_URL = 'https://cdn.filestackcontent.com';

    /**
     * Create the URL to send requests to based on action type
     *
     * @param string                $action     action type, possible values are:
     *                                          store, download, get_content, delete
     * @param string                $api_key    Filestack API Key
     * @param array                 $options    array of options
     * @param FilestackSecurity     $security   Filestack Security object
     *
     * @return string (url)
     */
    public static function createUrl($action, $api_key, $options=[], $security=null)
    {
        // lower case all keys
        $options = array_change_key_case($options, CASE_LOWER);

        $url = '';
        switch ($action) {
            case 'store':
                $allowed_options = [
                    'filename', 'mimetype', 'path', 'container', 'access', 'base64decode'
                ];

                // set location to S3 if not passed in
                $location = 'S3';
                if (array_key_exists('location', $options)) {
                    $location = $options['location'];
                }

                $url = sprintf('%s/store/%s?key=%s',
                    self::API_URL,
                    $location,
                    $api_key);

                foreach ($options as $key => $value) {
                    if (in_array($key, $allowed_options)) {
                        $url .= "&$key=$value";
                    }
                }
                break;

            case 'delete':
            case 'overwrite':
                $url = sprintf('%s/file/%s?key=%s',
                    self::API_URL,
                    $options['handle'],
                    $api_key
                );
                break;

            case 'transform':
                $base_url = sprintf('%s/%s',
                    self::PROCESSING_URL,
                    $api_key);

                // security in a different format for transformations
                $security_str = $security ? sprintf('/security=policy:%s,signature:%s',
                        $security->policy,
                        $security->signature) : '';

                $url = sprintf($base_url . $security_str . '/%s/%s',
                    $options['tasks_str'],
                    $options['handle']
                );
                break;

            default:
                break;
        }

        /**
         * sign url if security is passed in, ignoring transform called handle
         * in case statement above
         */
        if ($security && $action !== 'transform') {
            $url = $security->signUrl($url);
        }

        return $url;
    }

    /**
     * Get current version of this library (from VERSION file)
     */
    public static function getVersion()
    {
        $version = file_get_contents(__DIR__ . '/../VERSION');
        return trim($version);
    }
}
