<?php
namespace Filestack;

/**
 * Filestack config constants, such as base URLs
 */
class FilestackConfig
{
    const API_URL = 'https://www.filestackapi.com/api';
    const CDN_URL = 'https://cdn.filestackcontent.com';
    const UPLOAD_URL = 'https://upload.filestackapi.com';

    const UPLOAD_CHUNK_SIZE = 1024 * 1024 * 10; // 10MB
}
