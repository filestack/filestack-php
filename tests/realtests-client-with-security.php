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

        $destination = __DIR__ . '/../tests/testfiles/my-transformed-file.png';
        $transformed_file = $client->transform($url, $transform_tasks);

        $contents = $transformed_file->getContent();
        file_put_contents($destination, $contents);
        # or
        $result = $transformed_file->download($destination);

        $transformed_file->delete();

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

        $zipped_filelink->delete();

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

        $screenshot_filelink->delete();

        // converting a file
        $output_options = [
            'background' => 'white',
            'density' => 50,
            'compress' => true,
            'colorspace' => 'input',
            'quality' => 80,
            'strip' => true,
            'pageformat' => 'letter',
            'pageorientation' => 'landscape'
        ];

        $filelink = $client->convertFile($filelink->handle, 'pdf', $output_options);
        # print_r($filelink);

        $result = $filelink->download(__DIR__ . '/../tests/testfiles/convert-file-test.pdf');
        # or
        $contents = $filelink->getContent();
        file_put_contents(__DIR__ . '/../tests/testfiles/convert-file-test2.pdf', $contents);

        // delete a file from storage
        $result = $client->delete($filelink->handle);
        # var_dump($result);
    }
}
