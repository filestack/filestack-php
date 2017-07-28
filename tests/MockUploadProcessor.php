<?php
namespace Filestack\Tests;

use Filestack\UploadProcessor;

class MockUploadProcessor extends UploadProcessor
{
    public function callCreateParts($api_key, $metadata, $upload_data)
    {
        $result = $this->createParts($api_key, $metadata, $upload_data);
        return $result;
    }

    public function callProcessParts($parts)
    {
        $result = $this->processParts($parts);
        return $result;
    }
}