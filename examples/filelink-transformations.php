<?php
use Filestack\Filelink;
use Filestack\FilestackException;

$test_api_key = 'YOUR_FILESTACK_API_KEY';
$test_secret = 'YOUR_FILESTACK_SECURITY_SECRET';
$test_filepath = __DIR__ . '/../tests/testfiles/testing-download.jpg';

# Filestack client examples
$filelink = new Filelink('a-filestack-handle', $test_api_key);

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

/**
 * Chaining transformation tasks, you HAVE to call save() to save transformation
 * to storage, otherwise you'll just get a url to the transformation call.
 */

$chained_filelink = $filelink->crop(10, 20, 200, 200)
        ->rotate('red', 45)
        ->save();

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

/**
 * calling debug() will return a json item detailing
 * any errors the tasks may return
 */
$json_response = $filelink->rotate('00FF00', 45)
                ->detectFaces()
                ->debug();
