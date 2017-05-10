<?php
namespace Filestack\Test;

use Filestack\FilestackClient;
use Filestack\Filelink;
use Filestack\FilestackException;

class RealTestsFilelinkNoSecurity extends BaseTest
{
    public function testClientCalls()
    {
        if (!$this->run_real_tests) {
            $this->markTestSkipped(
                'Real calls to the API using a Filelink, comment out to test'
            );
        }

        $test_api_key = $this->test_api_key_no_sec;
        $test_handle = $this->test_file_handle_no_sec;
        $test_filepath = __DIR__ . '/../tests/testfiles/testing-download.jpg';

        # Filestack client examples
        $filelink = new Filelink($test_handle, $test_api_key);

        // get metadata
        $metadata = $filelink->getMetaData();

        // get content of a file
        $content = $filelink->getContent();

        // save file to local drive
        $filepath = __DIR__ . '/../tests/testfiles/' . $metadata['filename'];
        file_put_contents($filepath, $content);

        // download a file
        $filelink->download($test_filepath);

        // delete() and overwrite() methods require security

        // transformations
        $transform_tasks = [
            'crop'      => ['dim' => '[10,20,200,250]'],
            'resize'    => ['w' => '100', 'h' => '100'],
            'rotate'    => ['b' => '00FF00', 'd' => '45']
        ];

        $transformed_content = $filelink->transform($transform_tasks);

        // save file to local drive
        $filepath = __DIR__ . '/../tests/testfiles/transformed_file.jpg';
        file_put_contents($filepath, $transformed_content);

        // transformation save to destination
        $success = $filelink->transform($transform_tasks, $filepath);

        // chaining transformations
        $contents = $filelink->crop(10, 20, 200, 200)
                ->rotate('red', 45)
                ->downloadTransformed($filepath);

        # var_dump($contents);

        /*
         * must call resetTransform() to clear previous transformation calls if
         * you're using the same filelink instance that has been transformed before
        */
        $filelink->resetTransform();

        // transform then store to cloud
        $transformed_filelink = $filelink
                    ->circle()
                    ->blur(20)
                    ->store();

        # var_dump($transformed_filelink);
        # echo "\nnew transformed file cdn url is: " . $transformed_filelink->url();
    }
}
