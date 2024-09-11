<?php

namespace Filestack\Mixins;

trait LoggingTrait
{
    public function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        $formattedMessage = "[$timestamp] [LOG]: $message" . PHP_EOL;
        $filePath = './access.log';

        if (!file_exists($filePath)) {
            $file = fopen($filePath, 'w');

            if ($file === false) {
                throw new \RuntimeException('Unable to create file: ' . $filePath);
            }

            fclose($file);
        }

        file_put_contents($filePath, $formattedMessage, FILE_APPEND);
    }
}

