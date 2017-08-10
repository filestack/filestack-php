<?php
/**
 * Upload File via Intelligent Ingestion Examples
 *
 * The Intelligent Ingestion feature allows user to upload a file in chunks of
 * not precised size. This creates a more stable upload flow that ensures the
 * file being uploaded will eventually complete successfully, regardless of
 * network latency or timeout errors.
 *
 * However, the upload process may be slower than the normal upload flow for
 * large files, as there are errors are retried using the exponential backoff
 * retry strategy.
 *
 * Lastly, this feature has to be turned on for the apikey being used.  To turn
 * on this feature please contact Filestack at support@filestack.com.
 *
 */

use Filestack\FilestackClient;
use Filestack\Filelink;
use Filestack\FilestackException;

$test_api_key = 'YOUR_FILESTACK_API_KEY';
$test_filepath = __DIR__ . '/../tests/testfiles/some-big-video-file.mp4';

$client = new FilestackClient($test_api_key);

// upload a file
$filelink = null;

try {
    $filelink = $client->upload($test_filepath, ['intelligent' => true]);
    var_dump($filelink);
} catch (FilestackException $e) {
    echo $e->getMessage();
    echo $e->getCode();
}

// get metadata of file
$fields = [];
$metadata = $client->getMetaData($filelink->handle, $fields);
# or
$metadata = $client->getMetaData($filelink->url(), $fields);
var_dump($metadata);

