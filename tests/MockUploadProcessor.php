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

    public function callCommitPart($part)
    {
        $result = $this->commitPart($part);
        return $result;
    }

    public function callUploadChunkToS3($url, $headers, $chunk)
    {
        $result = $this->uploadChunkToS3($url, $headers, $chunk);
        return $result;
    }

    public function callRegisterComplete($api_key, $parts_etags,
                                         $upload_data, $metadata)
    {
        $result = $this->registerComplete($api_key, $parts_etags,
                                          $upload_data, $metadata);
        return $result;
    }
}