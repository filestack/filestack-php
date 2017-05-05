<?php
namespace Filestack\Test;

use Filestack\Filelink;
use Filestack\FilestackSecurity;
use Filestack\FilestackException;

class TransformationTests extends BaseTest
{
    /**
     * Test initializing Filelink intialized with handle and API Key
     */
    public function testTranformationSuccess()
    {
        $transform_tasks = [
            'crop'      => ['dim' => '[10,20,200,250]'],
            'resize'    => ['w' => '100', 'h' => '100'],
            'rotate'    => ['b' => '00FF00', 'd' => '45']
        ];

        $security = new FilestackSecurity($this->test_secret);
        $filelink = new Filelink($this->test_file_handle, $this->test_api_key);

        $destination = __DIR__ . '/testfiles/my-transformed-file.jpg';
        //$content = $filelink->transform($transform_tasks, $destination, $security);

        # or ?
        /*$transformed_filelink = $filelink
                                    ->crop($crop_options)
                                    ->rotate($rotate_options)
                                    ->resize($resize_options);*/
    }
}
