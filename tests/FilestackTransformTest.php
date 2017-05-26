<?php
namespace Filestack\Tests;

use Filestack\FilestackConfig;
use Filestack\Filelink;
use Filestack\FilestackSecurity;
use Filestack\FilestackException;

class FilestackTransformTest extends BaseTest
{
    /**
     * Test debugging a transformation url of a chained call
     */
    public function testDebuggingTransformCalls()
    {
        $mock_response = new MockHttpResponse(
            200,
            '{"apikey": "someapikey", "errors": "some errors"}'
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $filelink = new Filelink($this->test_file_handle, $this->test_api_key,
            $this->test_security, $stub_http_client);

        $json_response = $filelink->rotate('00FF00', 45)
                ->detectFaces()
                ->debug();

        $this->assertNotNull($json_response);
    }

    /**
     * Test the transform method on a Filelink returning contents
     */
    public function testTransformationSuccess()
    {
        $mock_response = new MockHttpResponse(
            200,
            $this->mock_response_json
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $transform_tasks = [
            'crop'      => ['d' => '[10,20,200,250]'],
            'resize'    => ['w' => '100', 'h' => '100'],
            'rotate'    => ['b' => '00FF00', 'd' => '45']
        ];

        $filelink = new Filelink($this->test_file_handle, $this->test_api_key,
                        $this->test_security, $stub_http_client);

        $contents = $filelink->transform($transform_tasks);
        $this->assertNotNull($contents);
    }

    /**
     * Test the transform method on a Filelink saving to a location
     */
    public function testTransformationWithDest()
    {
        $mock_response = new MockHttpResponse(
            200,
            $this->mock_response_json
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $transform_tasks = [
            'crop'      => ['d' => '[10,20,200,250]']
        ];

        $filelink = new Filelink($this->test_file_handle, $this->test_api_key,
                        $this->test_security, $stub_http_client);

        $new_filelink = $filelink->transform($transform_tasks);

        $this->assertNotNull($new_filelink);
    }

    /**
     * Test the transform method throws exception if api call failed
     */
    public function testTransformationFailed()
    {
        $mock_response = new MockHttpResponse(
            403,
            'Forbidden, Missing credentials'
        );

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(403);

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $transform_tasks = [
            'resize'    => ['w' => '100', 'h' => '100']
        ];

        $filelink = new Filelink($this->test_file_handle, $this->test_api_key,
                        $this->test_security, $stub_http_client);
        $filelink->transform($transform_tasks);
    }

    /**
     * Test the transform method throws exception if task is invalid
     */
    public function testTransformInvalidTaskThrowsException()
    {
        $mock_response = new MockHttpResponse(
            400,
            'Invalid transformation task'
        );

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $transform_tasks = [
            'some_bad_task'    => ['w' => '100', 'h' => '100']
        ];

        $filelink = new Filelink($this->test_file_handle, $this->test_api_key,
                        $this->test_security, $stub_http_client);
        $filelink->transform($transform_tasks);
    }

    /**
     * Test the transform method throws exception if passed in invalid attribute
     */
    public function testTransformInvalidAttrThrowsException()
    {
        $mock_response = new MockHttpResponse(
            400,
            'Invalid transformation attribute'
        );

        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $transform_tasks = [
            'crop'    => ['bad_attr' => '100']
        ];

        $filelink = new Filelink($this->test_file_handle, $this->test_api_key,
                        $this->test_security, $stub_http_client);
        $filelink->transform($transform_tasks);
    }

    /**
     * Test getting a transformation url of a chained call
     */
    public function testTransformChainUrl()
    {
        $filelink = new Filelink($this->test_file_handle, $this->test_api_key);
        $filelink->crop(10, 20, 200, 250)
                ->rotate('00FF00', 45);

        $expected_url = sprintf('%s/%s/%s',
            FilestackConfig::CDN_URL,
            'crop=d:%5B10%2C20%2C200%2C250%5D/rotate=b:00FF00,d:45,e:false',
            $filelink->handle
        );
        $this->assertEquals($expected_url, $filelink->transform_url);

        $expected_url = sprintf('%s/%s/%s',
            FilestackConfig::CDN_URL,
            'resize=w:100,h:100,f:clip,a:center',
            $filelink->handle
        );

        $filelink->resetTransform();
        $filelink->resize(100, 100);
        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test getting a transformation url of a chained call
     */
    public function testTransformInvalidMethod()
    {
        $this->expectException(FilestackException::class);
        $this->expectExceptionCode(400);

        $filelink = new Filelink($this->test_file_handle, $this->test_api_key);
        $filelink->crop_bad_method(10, 20, 200, 250);
    }

    /**
     * Test chaining transformation with download call
     */
    public function testTransformChainDownload()
    {
        $mock_response = new MockHttpResponse(
            200,
            new MockHttpResponseBody("some content")
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $filelink = new Filelink($this->test_file_handle,
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $destination = __DIR__ . '/testfiles/my-transformed-file.jpg';

        $filelink = $filelink
                        ->resize(100, 100)
                        ->download($destination);

        $this->assertNotNull($filelink);
    }

    /**
     * Test chaining transformation with store call
     */
    public function testTransformChainStore()
    {
        $mock_response = new MockHttpResponse(
            200,
            json_encode([
                'filename'  => 'somefilename.jpg',
                'size'      => '1000',
                'type'      => 'image/jpg',
                'url'       => 'https://cdn.filestack.com/somefilehandle'
            ])
        );

        $stub_http_client = $this->createMock(\GuzzleHttp\Client::class);
        $stub_http_client->method('request')
             ->willReturn($mock_response);

        $filelink = new Filelink($this->test_file_handle,
            $this->test_api_key,
            $this->test_security,
            $stub_http_client
        );

        $transformed_filelink = $filelink
                        ->sepia()
                        ->circle()
                        ->blur(20)
                        ->save();

        $this->assertNotNull($transformed_filelink);
        $this->assertEquals($transformed_filelink->metadata['filename'], 'somefilename.jpg');
    }

    /**
     * Test ascii transformation call
     */
    public function testAsciiSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $background = 'black';
        $colored = true;
        $foreground='white';
        $reverse = false;
        $size=100;

        $filelink->ascii($background, $colored,
            $foreground, $reverse, $size);

        $expected_transform_str = 'ascii=b:black,c:true,f:white,r:false,s:100';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test blackwhite transformation call
     */
    public function testBlackwhiteSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $threshold = 50;
        $filelink->blackWhite($threshold);

        $expected_transform_str = 'blackwhite=t:50';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test blur transformation call
     */
    public function testBlurSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $amount = 10;
        $filelink->blur($amount);

        $expected_transform_str = 'blur=a:10';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test border transformation call
     */
    public function testBorderSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $background = 'gray';
        $color = 'black';
        $width = 10;

        $filelink->border($background, $color, $width);

        $expected_transform_str = 'border=b:gray,c:black,w:10';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test circle transformation call
     */
    public function testCircleSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $background = 'gray';
        $filelink->circle($background);

        $expected_transform_str = 'circle=b:gray';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test circle transformation call
     */
    public function testCollageSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $files = [
            '9K1BZLt6SAyztVaOtAQ4',
            'FWOrzDcpREanJDI3hdR5',
            'Vi6RUEi6TgCSo9FXYVxP',
            'https://d1wtqaffaaj63z.cloudfront.net/images/E-0510.JPG'
        ];

        $width = 800;
        $height = 600;
        $color='white';
        $fit='auto';
        $margin=10;
        $auto_rotate=false;

        $filelink->collage($files, $width, $height,
            $color, $fit, $margin, $auto_rotate);

        $expected_transform_str = sprintf('collage=f:%s' .
            ',w:800,h:600,c:white,i:auto,m:10,a:false',
            urlencode(json_encode($files))
        );

        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test compress transformation call
     */
    public function testCompressSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $filelink->compress(true);

        $expected_transform_str = 'compress=m:true';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test crop transformation call
     */
    public function testCropSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $x_coordinate = 10;
        $y_coordinate = 10;
        $width = 200;
        $height = 200;
        $filelink->crop($x_coordinate, $y_coordinate, $width, $height);

        $expected_transform_str = 'crop=d:'. urlencode('[10,10,200,200]');
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test detect_faces transformation call
     */
    public function testDetectFacesSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $color='black';
        $export=false;
        $min_size=0.5;
        $max_size=1;

        $filelink->detectFaces($color, $export, $min_size, $max_size);

        $expected_transform_str = 'detect_faces=c:black,e:false,n:0.5,x:1';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test enhance transformation call
     */
    public function testEnhanceSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $filelink->enhance();

        $expected_transform_str = 'enhance';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test modulate transformation call
     */
    public function testModulateSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $brightness=50;
        $hue=10;
        $saturation=75;

        $filelink->modulate($brightness, $hue, $saturation);

        $expected_transform_str = 'modulate=b:50,h:10,s:75';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test monochrome transformation call
     */
    public function testMonochromeSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $filelink->monochrome();

        $expected_transform_str = 'monochrome';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test negative transformation call
     */
    public function testNegativeSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $filelink->negative();

        $expected_transform_str = 'negative';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test oil_paint transformation call
     */
    public function testOilPaintSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $amount = 50;
        $filelink->oilPaint($amount);

        $expected_transform_str = 'oil_paint=a:50';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test partial_blur transformation call
     */
    public function testPartialBlurSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $amount=10;
        $blur=4;
        $objects=[[10,20,200,250]];
        $type='oval';

        $filelink->partialBlur($amount, $blur, $objects, $type);

        $expected_transform_str = 'partial_blur=a:10,l:4,o:' .
                                   urlencode('[[10,20,200,250]]') . ',t:oval';

        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test partial_pixelate transformation call
     */
    public function testPartialPixelateSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $amount=10;
        $blur=4;
        $objects=[[10,20,200,250]];
        $type='oval';

        $filelink->partialPixelate($amount, $blur, $objects, $type);

        $expected_transform_str = 'partial_pixelate=a:10,l:4,o:' .
                                   urlencode('[[10,20,200,250]]') . ',t:oval';

        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test pixelate transformation call
     */
    public function testPixelateSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $amount = 10;
        $filelink->pixelate($amount);

        $expected_transform_str = 'pixelate=a:10';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test polaroid transformation call
     */
    public function testPolaroidSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $background='gray';
        $color='white';
        $rotate=45;

        $filelink->polaroid($background, $color, $rotate);

        $expected_transform_str = 'polaroid=b:gray,c:white,r:45';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test quality transformation call
     */
    public function testQualitySuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $value = 70;
        $filelink->quality($value);

        $expected_transform_str = 'quality=v:70';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test redeye transformation call
     */
    public function testRedEyeSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $filelink->redEye();

        $expected_transform_str = 'redeye';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test resize transformation call
     */
    public function testResizeSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $width = 100;
        $height = 100;
        $fit='scale';
        $align='left';

        $filelink->resize($width, $height, $fit, $align);

        $expected_transform_str = 'resize=w:100,h:100,f:scale,a:left';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test rounded_corners transformation call
     */
    public function testRoundedCornersSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $background='white';
        $blur=0.3;
        $radius=5;

        $filelink->roundedCorners($background, $blur, $radius);

        $expected_transform_str = 'rounded_corners=b:white,l:0.3,r:5';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test rotate transformation call
     */
    public function testRotateSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $background = 'white';
        $deg = 90;
        $exif = false;

        $filelink->rotate($background, $deg, $exif);

        $expected_transform_str = 'rotate=b:white,d:90,e:false';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test sepia transformation call
     */
    public function testSepiaSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $tone = 50;
        $filelink->sepia($tone);

        $expected_transform_str = 'sepia=t:50';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test sharpen transformation call
     */
    public function testSharpenSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $amount = 50;
        $filelink->sharpen($amount);

        $expected_transform_str = 'sharpen=a:50';
        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test shadow transformation call
     */
    public function testShadowSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $background = 'white';
        $blur = 4;
        $opacity = 60;
        $vector = [4,4];

        $filelink->shadow($background, $blur, $opacity, $vector);

        $expected_transform_str = 'shadow=b:white,l:4,o:60,v:' .
            urlencode(json_encode($vector));

        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test torn_edges transformation call
     */
    public function testTornEdgesSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $background='white';
        $spread=[1,10];

        $filelink->tornEdges($background, $spread);

        $expected_transform_str = 'torn_edges=b:white,s:' .
            urlencode(json_encode($spread));

        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test upscale transformation call
     */
    public function testUpscaleSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $noise = 'none';
        $style = 'photo';
        $upscale = true;

        $filelink->upscale($noise, $style, $upscale);

        $expected_transform_str = 'upscale=n:none,s:photo,u:true';

        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test vignette transformation call
     */
    public function testVignetteSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $amount = 20;
        $background = 'white';
        $blurmode = 'gaussian';

        $filelink->vignette($amount, $background, $blurmode);

        $expected_transform_str = 'vignette=a:20,b:white,m:gaussian';

        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }

    /**
     * Test watermark transformation call
     */
    public function testWatermarkSuccess()
    {
        $filelink = new Filelink($this->test_file_handle,
                        $this->test_api_key,
                        $this->test_security);

        $file_handle = $this->test_file_handle;
        $position = 'center';
        $size = 30;

        $filelink->watermark($file_handle, $position, $size);

        $expected_transform_str = 'watermark=f:' . $file_handle . ',p:center,s:30';

        $expected_url = sprintf('%s/security=policy:%s,signature:%s/%s/%s',
                FilestackConfig::CDN_URL,
                $this->test_security->policy,
                $this->test_security->signature,
                $expected_transform_str,
                $this->test_file_handle
            );

        $this->assertEquals($expected_url, $filelink->transform_url);
    }
}
