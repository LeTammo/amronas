<?php

namespace App\Service;

class LoggerService
{
    static function log(string $type, string $message): void
    {
        $dir = __DIR__ . "/../../logs/";
        $filename = sprintf("%s%s.log", $dir, $type);

        if (!is_dir($dir)) {
            mkdir($dir);
        }

        $content = sprintf("[%s] %s\n", (new \DateTime())->format('Y-m-d_H:i:s'), $message);

        file_put_contents($filename, $content, FILE_APPEND);
    }
}