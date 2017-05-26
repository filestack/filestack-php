<?php
use Filestack\FilestackClient;
use Filestack\FilestackSecurity;
use Filestack\Filelink;
use Filestack\FilestackException;

$test_api_key = 'YOUR_FILESTACK_API_KEY';
$test_secret = 'YOUR_FILESTACK_SECURITY_SECRET';

$security = new FilestackSecurity($test_secret);
$client = new FilestackClient($test_api_key, $security);

/**
 * IMPORTANT: To use the audio conversion function, you must setup webhooks to
 * notify you when the transcoding has completed. See our online documentation
 * for more details:
 * https://www.filestack.com/docs/audio-transformations
 */

// transcoding video with client
$source = 'Q5eBTKldRfCSuEjUYuAz';
$output_options = [
    'access'                => 'public',
    'aspect_mode'           => 'letterbox',
    'audio_bitrate'         => 256,
    'audio_channels'        => 2,
    'audio_sample_rate'     => 44100,
    'fps'                   => 60,
    'title'                 => 'test Filestack Audio conversion',
    'video_bitrate'         => 1024,
    'watermark_top'         => 10,
    'watermark_url'         => 'Bc2FQwXReueTsaeXB6rO'
];

$force = true;
$result = $client->convertVideo($source, 'm4a', $output_options, $force);
$uuid = $result['uuid'];
$conversion_url = $result['conversion_url'];

$info = $client->getConvertTaskInfo($conversion_url);
$status = $info['status'];
if ($status === 'completed') {
    $thumbnail = $info['data']['thumb'];
    $url = $info['data']['url'];
}
# echo "\nvideo conversion, uuid=$uuid\n";

// transcoding video with filelink
$filelink = new Filelink('Q5eBTKldRfCSuEjUYuAz', $test_api_key, $security);

$output_options = [
    'access'                => 'public',
    'aspect_mode'           => 'letterbox',
    'audio_bitrate'         => 256,
    'audio_channels'        => 2,
    'audio_sample_rate'     => 44100,
    'fps'                   => 60,
    'title'                 => 'test Filestack Audio conversion',
    'video_bitrate'         => 1024,
    'watermark_top'         => 10,
    'watermark_url'         => 'Bc2FQwXReueTsaeXB6rO'
];

$result = $filelink->convertVideo('m4a', $output_options);

// check the status of a conversion task
$info = $client->getConvertTaskInfo($result['conversion_url']);
# vardump($info);

$status = $info['status'];
if ($status === 'completed') {
    $thumbnail = $info['data']['thumb'];
    $url = $info['data']['url'];
}

# Example video transcoding task callback data, which will post to your webhook
# once the conversion task is completed
/*
{
    "status":"completed",
    "message":"Done",
    "data":{
        "thumb":"https://cdn.filestackcontent.com/f1e8V88QDuxzOvtOAq1W",
        "thumb100x100":"https://process.filestackapi.com/AhTgLagciQByzXpFGRI0Az/resize=w:100,h:100,f:crop/output=f:jpg,q:66/https://cdn.filestackcontent.com/f1e8V88QDuxzOvtOAq1W",
        "thumb200x200":"https://process.filestackapi.com/AhTgLagciQByzXpFGRI0Az/resize=w:200,h:200,f:crop/output=f:jpg,q:66/https://cdn.filestackcontent.com/f1e8V88QDuxzOvtOAq1W",
        "thumb300x300":"https://process.filestackapi.com/AhTgLagciQByzXpFGRI0Az/resize=w:300,h:300,f:crop/output=f:jpg,q:66/https://cdn.filestackcontent.com/f1e8V88QDuxzOvtOAq1W",
        "url":"https://cdn.filestackcontent.com/VgvFVdvvTkml0WXPIoGn"
    },
    "metadata":{
        "result":{
            "audio_channels":2,
            "audio_codec":"vorbis",
            "audio_sample_rate":44100,
            "created_at":"2015/12/21 20:45:19 +0000",
            "duration":10587,
            "encoding_progress":100,
            "encoding_time":8,
            "extname":".webm",
            "file_size":293459,
            "fps":24,
            "height":260,
            "mime_type":"video/webm",
            "started_encoding_at":"2015/12/21 20:45:22 +0000",
            "updated_at":"2015/12/21 20:45:32 +0000",
            "video_bitrate":221,
            "video_codec":"vp8",
            "width":300
        },
        "source":{
            "audio_bitrate":125,
            "audio_channels":2,
            "audio_codec":"aac",
            "audio_sample_rate":44100,
            "created_at":"2015/12/21 20:45:19 +0000",
            "duration":10564,
            "extname":".mp4",
            "file_size":875797,
            "fps":24,
            "height":360,
            "mime_type":"video/mp4",
            "updated_at":"2015/12/21 20:45:32 +0000",
            "video_bitrate":196,
            "video_codec":"h264",
            "width":480
        }
    },
    "timestamp":"1453850583",
    "uuid":"638311d89d2bc849563a674a45809b7c"
}
*/

// transcoding audio with client
$source = 'https://upload.wikimedia.org/wikipedia/commons/b/b5/'.
            'Op.14%2C_Scherzo_No.2_in_C_minor_for_piano%2C_C._Schumann.ogg';
$output_options = [
    'access'                => 'public',
    'audio_bitrate'         => 256,
    'audio_channels'        => 2,
    'audio_sample_rate'     => 44100,
    'force'                 => true,
    'title'                 => 'test Filestack Audio conversion'
];

$result = $client->convertAudio($source, 'mp3', $output_options);

// check the status of a conversion task
$info = $client->getConvertTaskInfo($result['conversion_url']);
# vardump($info);

$status = $info['status'];
if ($status === 'completed') {
    $thumbnail = $info['data']['thumb'];
    $url = $info['data']['url'];
}
# echo "\naudio conversion, uuid=$uuid\n";

// audio conversion with filelink
$filelink = new Filelink('Q5eBTKldRfCSuEjUYuAz', $test_api_key, $security);

$output_options = [
    'access'                => 'public',
    'audio_bitrate'         => 256,
    'audio_channels'        => 2,
    'audio_sample_rate'     => 44100,
    'force'                 => true,
    'title'                 => 'test Filestack Audio conversion'
];

$result = $filelink->convertAudio('mp3', $output_options);

// check the status of a conversion task
$info = $filelink->getConvertTaskInfo($result['conversion_url']);
# vardump($info);

$status = $info['status'];
if ($status === 'completed') {
    $thumbnail = $info['data']['thumb'];
    $url = $info['data']['url'];
}
# echo "\naudio conversion, uuid=$uuid\n";


// example audio transcoding task callback data
/*
{
    "data":{
        "url":"https://cdn.filestackcontent.com/2xIn8g3AQdmfFPD5Brkk"
    },
    "metadata":{
        "result":{
            "audio_bitrate":64,
            "audio_channels":2,
            "audio_codec":"mp3",
            "audio_sample_rate":48000,
            "created_at":"2016/02/25 07:16:08 +0000",
            "duration":251472,
            "encoding_progress":100,
            "encoding_time":16,
            "extname":".mp3",
            "file_size":2.012013e+06,
            "mime_type":"audio/mpeg",
            "started_encoding_at":"2016/02/25 07:16:09 +0000",
            "updated_at":"2016/02/25 07:16:27 +0000"
        },
        "source":{
            "audio_bitrate":128,
            "audio_channels":2,
            "audio_codec":"vorbis",
            "audio_sample_rate":44100,
            "created_at":"2016/02/25 07:16:08 +0000",
            "duration":251427,
            "extname":".ogg",
            "file_size":3.062342e+06,
            "mime_type":"audio/ogg",
            "updated_at":"2016/02/25 07:16:27 +0000"
        }
    },
    "status":"completed",
    "timestamp":"1456384626",
    "uuid":"57694303a5d29c148154d5f706b4c256"
}
*/
