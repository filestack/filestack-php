<?php

use Filestack\FilestackClient;
use Filestack\FilestackSecurity;
use Filestack\Filelink;
use Filestack\FilestackException;

class RealTestsClientWithSecurity extends \PHPUnit_Framework_TestCase
{
    public function testClientCalls()
    {
        $this->markTestSkipped(
            'Real calls to the API using the Filestack client, comment out to test'
        );

        $test_api_key = 'A5lEN6zU8SemSBWiwcGJhz';
        $test_secret = '3UAQ64UWMNCCRF36CY2NSRSPSU';
        $test_filepath = __DIR__ . '/../tests/testfiles/calvinandhobbes.jpg';

        # Filestack client examples
        $security = new FilestackSecurity($test_secret);
        $client = new FilestackClient($test_api_key, $security);

        // upload a file
        $options = ['Filename' => 'somefilename.jpg'];
        try {
            $filelink = $client->upload($test_filepath, $options);
            var_dump($filelink);
        } catch (FilestackException $e) {
            echo $e->getMessage();
            echo $e->getCode();
        }

        // get metadata of file
        $fields = [];
        $metadata = $client->getMetaData($filelink->url(), $fields);
        var_dump($metadata);

        // get content of a file
        $content = $client->getContent($filelink->url());

        // save file to local drive
        $filepath = __DIR__ . '/../tests/testfiles/' . $metadata['filename'];
        file_put_contents($filepath, $content);

        // download a file
        $destination = __DIR__ . '/../tests/testfiles/my-custom-filename.jpg';
        $result = $client->download($filelink->url(), $destination);
        var_dump($result);

        // overwrite a file
        $filelink2 = $client->overwrite($test_filepath, $filelink->handle);
        var_dump($filelink2);

        // delete a file
        $result = $client->delete($filelink->handle);
        var_dump($result);
    }
}
