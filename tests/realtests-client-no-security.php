<?php
namespace Filestack\Test;

use Filestack\FilestackClient;
use Filestack\Filelink;
use Filestack\FilestackException;

class RealTestsClientNoSecurity extends BaseTest
{
    public function testClientCalls()
    {
        if (!$this->run_real_tests) {
            $this->markTestSkipped(
                'Real calls to the API using the Filestack client'
            );
        }

        $test_api_key = $this->test_api_key_no_sec;
        $test_filepath = __DIR__ . '/../tests/testfiles/calvinandhobbes.jpg';

        # Filestack client examples
        $client = new FilestackClient($test_api_key);

        // upload a file
        $options = ['Filename' => 'somefilename.jpg'];
        $filelink = null;
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
        $destination = __DIR__ . '/../tests/testfiles/' . $metadata['filename'];
        file_put_contents($destination, $content);

        // download a file
        $destination = __DIR__ . '/../tests/testfiles/my-custom-filename.jpg';
        $result = $client->download($filelink->url(), $destination);
        # var_dump($result);

        // transform an image from url
        $url = "https://cdn.filestackcontent.com/vA9vFnjRVGmEbNPy3beQ";
        $transform_tasks = [
            'crop'      => ['dim' => '[10,20,200,250]'],
            'resize'    => ['w' => '100', 'h' => '100'],
            'rotate'    => ['b' => '00FF00', 'd' => '45'],
        ];

        $destination = __DIR__ . '/../tests/testfiles/my-transformed-file.png';
        $transformed_file = $client->transform($url, $transform_tasks);

        $contents = $transformed_file->getContent();
        file_put_contents($destination, $contents);
        # or
        $result = $transformed_file->download($destination);

        // overwriting a file will fail as security is required for this operation
        // deleting a file will fail as securiy is required for this operation

        // zipping files
        $sources = [
            'https://d1wtqaffaaj63z.cloudfront.net/images/20150617_143146.jpg',
            $filelink->handle
        ];

        $zipped_filelink = $client->zip($sources);
        $destination = __DIR__ . '/../tests/testfiles/contents-zipped.zip';

        $result = $zipped_filelink->download($destination);
        # or
        $contents = $zipped_filelink->getContent();
        file_put_contents($destination, $contents);

        // creating a collage
        $sources = [
            '9K1BZLt6SAyztVaOtAQ4',
            'FWOrzDcpREanJDI3hdR5',
            'Vi6RUEi6TgCSo9FXYVxP',
            'https://d1wtqaffaaj63z.cloudfront.net/images/E-0510.JPG'
        ];

        $collage_filelink = $client->collage($sources, 800, 600);
        $destination = __DIR__ . '/../tests/testfiles/collage-test.png';

        $result = $collage_filelink->download($destination);
        # or
        $contents = $collage_filelink->getContent();
        file_put_contents($destination, $contents);

        // take a screenshot of a url
        $url = 'https://en.wikipedia.org/wiki/Main_Page';
        $screenshot_filelink = $client->screenshot($url);
        $destination = __DIR__ . '/../tests/testfiles/screenshot-test.png';

        $result = $screenshot_filelink->download($destination);
        # or get contents then save
        $contents = $screenshot_filelink->getContent();
        file_put_contents($destination, $contents);
    }
}
