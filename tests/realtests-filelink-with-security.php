<?php
namespace Filestack\Test;

use Filestack\FilestackClient;
use Filestack\Filelink;
use Filestack\FilestackException;
use Filestack\FilestackSecurity;

class RealTestsFilelinkWithSecurity extends BaseTest
{
    public function testFilelinkCalls()
    {
        if (!$this->run_real_tests) {
            $this->markTestSkipped(
                'Real calls to the API using a Filelink with Security, comment out to test'
            );
        }

        $test_api_key = $this->test_api_key;
        $test_secret = $this->test_secret;
        $test_filepath = __DIR__ . '/../tests/testfiles/calvinandhobbes.jpg';

        // upload a file to test
        $security = new FilestackSecurity($test_secret);
        $client = new FilestackClient($test_api_key, $security);
        $uploaded_filelink = $client->upload($test_filepath);

        # Filestack client examples
        $file_handle = $uploaded_filelink->handle;
        $filelink = new Filelink($file_handle,
            $test_api_key, $security);

        // get metadata
        $metadata = $filelink->getMetaData();
        # var_dump($metadata);

        // get content of a file
        $content = $filelink->getContent();
        # var_dump($content);

        // save file to local drive
        $filepath = __DIR__ . '/../tests/testfiles/' . $metadata['filename'];
        file_put_contents($filepath, $content);

        // download a file
        $filelink->download($test_filepath);

        // overwrite remote file with local file
        $filelink->overwrite($test_filepath);

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

        $converted_filelink = $filelink->convertFile('pdf', $output_options);
        # print_r($converted_filelink);

        $result = $converted_filelink->download(__DIR__ . '/../tests/testfiles/convert-filelink-test.pdf');
        # or
        $contents = $converted_filelink->getContent();
        file_put_contents(__DIR__ . '/../tests/testfiles/convert-filelink-test2.pdf', $contents);

        // converting audio file
        $audio_filelink = new Filelink('Q5eBTKldRfCSuEjUYuAz', $this->test_api_key,
                        $this->test_security);

        $output_options = [
            'access'                => 'public',
            'audio_bitrate'         => 256,
            'audio_channels'        => 2,
            'audio_sample_rate'     => 44100,
            'force'                 => true,
            'title'                 => 'test Filestack Audio conversion'
        ];

        $uuid = $audio_filelink->convertAudio('mp3', $output_options);
        echo "\naudio conversion, uuid=$uuid\n";

        // converting video file
        $video_filelink = new Filelink('Q5eBTKldRfCSuEjUYuAz', $this->test_api_key,
                        $this->test_security);

        $output_options = [
            'access'                => 'public',
            'aspect_mode'           => 'letterbox',
            'audio_bitrate'         => 256,
            'audio_channels'        => 2,
            'audio_sample_rate'     => 44100,
            'fps'                   => 60,
            'force'                 => true,
            'title'                 => 'test Filestack Audio conversion',
            'video_bitrate'         => 1024,
            'watermark_top'         => 10,
            'watermark_url'         => 'Bc2FQwXReueTsaeXB6rO'
        ];

        $uuid = $video_filelink->convertVideo('m4a', $output_options);
        echo "\nvideo conversion, uuid=$uuid\n";

        // transformations
        $transform_tasks = [
            'crop'      => ['dim' => '[10,20,200,250]'],
            'resize'    => ['w' => '100', 'h' => '100'],
            'rotate'    => ['b' => '00FF00', 'd' => '45']
        ];

        $transformed_filelink = $filelink->transform($transform_tasks);

        $destination = __DIR__ . '/../tests/testfiles/transformed_file.jpg';

        // download transformed file
        $result = $transformed_filelink->download($destination);
        # or save file to local drive
        $transformed_content = $transformed_filelink->getContent();
        file_put_contents($destination, $transformed_content);

        // chaining transformations
        $chained_filelink = $filelink->crop(10, 20, 200, 200)
                ->rotate('red', 45)
                ->save(); /**
                            You HAVE to call save() to save transformation to storage,
                            otherwise you'll just get a url to the transformation call.
                          */

        $destination = __DIR__ . '/../tests/testfiles/chained-transformation.png';

        // download transformed file
        $result = $chained_filelink->download($destination);
        # or save file to local drive
        $chained_content = $chained_filelink->getContent();
        file_put_contents($destination, $chained_content);

        /**
         * must call resetTransform() to clear previous transformation calls if
         * you're using the same filelink instance that has been transformed before
         * for new transformations
         */
        $filelink->resetTransform();

        // download and save a zipped a transformed filelink
        $destination = __DIR__ . '/../tests/testfiles/my-zipped-contents.zip';
        $result = $filelink->rotate('00FF00', 45)
            ->zip()
            ->download($destination);

        // delete remote file
        $success = $filelink->delete();
        # echo "\n deleted $success";
    }
}
