<?php
namespace Filestack;

/**
 * Filestack config constants, such as base URLs
 */
class FilestackConfig
{
    const API_URL = 'https://www.filestackapi.com/api';
    const PROCESS_URL = 'https://process.filestackapi.com';
    const CDN_URL = 'https://cdn.filestackcontent.com';
    const UPLOAD_URL = 'https://upload.filestackapi.com';

    // Custom CNAME templates. Replace __CNAME__.
    const CNAME_NEEDLE = '__CNAME__';
    const CNAME_TEMPLATE = array(
      self::API_URL => 'https://www.__CNAME__/api',
      self::PROCESS_URL => 'https://process.__CNAME__',
      self::CDN_URL => 'https://cdn.__CNAME__',
      self::UPLOAD_URL => 'https://upload.__CNAME__',
    );

    const UPLOAD_PART_SIZE = 1024 * 1024 * 8; // last_digit=MB
    const UPLOAD_CHUNK_SIZE = 1024 * 1024 * 1; // last_digit=MB
    const UPLOAD_MIN_CHUNK_SIZE = 1024 * 32; // last_digit=KB

    const UPLOAD_WAIT_ATTEMPTS = 300;
    const UPLOAD_WAIT_SECONDS = 2;
    const UPLOAD_TIMEOUT_SECONDS = 30;
    const MAX_RETRIES = 5;
}
