<?php
namespace Filestack\Test;

use Filestack\FilestackClient;
use Filestack\FilestackSecurity;
use Filestack\Filelink;
use Filestack\FilestackException;

class RealTestsClientWithSecurity extends BaseTest
{
    public function testClientCalls()
    {
        if (!$this->run_real_tests) {
            $this->markTestSkipped(
                'Real calls to the API using the Filestack client, comment out to test'
            );
        }

        $test_api_key = $this->test_api_key;
        $test_secret = $this->test_secret;
        $test_filepath = __DIR__ . '/../tests/testfiles/calvinandhobbes.jpg';

        # Filestack client examples
        $security = new FilestackSecurity($test_secret);
        $client = new FilestackClient($test_api_key, $security);

        // upload a file
        $options = ['Filename' => 'somefilename.jpg'];
        try {
            $filelink = $client->upload($test_filepath, $options);
            # var_dump($filelink);
        } catch (FilestackException $e) {
            echo $e->getMessage();
            echo $e->getCode();
        }

        // get metadata of file
        $fields = [];
        $metadata = $client->getMetaData($filelink->url(), $fields);
        # var_dump($metadata);

        // get content of a file
        $content = $client->getContent($filelink->url());

        // save file to local drive
        $filepath = __DIR__ . '/../tests/testfiles/' . $metadata['filename'];
        file_put_contents($filepath, $content);

        // download a file
        $destination = __DIR__ . '/../tests/testfiles/my-custom-filename.jpg';
        $result = $client->download($filelink->url(), $destination);
        # var_dump($result);

        // overwrite a file
        $filelink2 = $client->overwrite($test_filepath, $filelink->handle);
        # var_dump($filelink2);

        // transform an image from url
        $url = "https://cdn.filestackcontent.com/vA9vFnjRVGmEbNPy3beQ";
        $transform_tasks = [
            'crop'      => ['dim' => '[10,20,200,250]'],
            'resize'    => ['w' => '100', 'h' => '100'],
            'rotate'    => ['b' => '00FF00', 'd' => '45'],
        ];

        $content = $client->transform($url, $transform_tasks);

        // save file to local drive
        $filepath = __DIR__ . '/../tests/testfiles/transformed_file.jpg';
        file_put_contents($filepath, $content);

        // transform an image from url with store()
        $url = "https://cdn.filestackcontent.com/vA9vFnjRVGmEbNPy3beQ";
        $transform_tasks = [
            'resize'    => ['width' => '100', 'height' => '100'],
            'rotate'    => ['background' => 'red', 'deg' => '45'],
            'store'     => []
        ];

        $result = $client->transform($url, $transform_tasks);
        $json = json_decode($result);
        # var_dump($json);

        // delete a file
        $result = $client->delete($filelink->handle);
        # var_dump($result);
    }
}
