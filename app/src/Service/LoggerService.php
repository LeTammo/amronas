<?php

namespace App\Service;

class LoggerService
{
    static function log(string $type, string $message)
    {
        $filename = sprintf("%s%s.log", __DIR__ . "/../../logs/", $type);

        $content = sprintf("[%s] %s\n", (new \DateTime())->format('Y-m-d_H:i:s'), $message);

        file_put_contents($filename, $content, FILE_APPEND);
    }
}