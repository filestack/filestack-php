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
    // const UPLOAD_URL = 'https://upload.filestackapi.com';
    const UPLOAD_URL = 'https://rc-upload.filestackapi.com';

    const UPLOAD_PART_SIZE = 1024 * 1024 * 8; // last_digit=MB
    const UPLOAD_CHUNK_SIZE = 1024 * 1024 * 1; // last_digit=MB
    const UPLOAD_WAIT_ATTEMPTS = 300;
    const UPLOAD_WAIT_SECONDS = 2;

    const ACCEPTED_NOT_COMPLETE_CODE = 202;
}
