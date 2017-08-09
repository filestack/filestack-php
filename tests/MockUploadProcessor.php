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

    public function callProcessChunks($part, $chunks)
    {
        $result = $this->processChunks($part, $chunks);
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

    public function callMultipartGetTags($part_num, $s3_results, &$parts_etags)
    {
        $this->multipartGetTags($part_num, $s3_results, $parts_etags);
    }

    public function callRegisterComplete($api_key, $parts_etags,
                                         $upload_data, $metadata)
    {
        $result = $this->registerComplete($api_key, $parts_etags,
                                          $upload_data, $metadata);
        return $result;
    }

    public function callHandleS3PromisesResult($s3_results)
    {
        $result = $this->handleS3PromisesResult($s3_results);
        return $result;
    }
}