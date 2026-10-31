<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    /** APP_CACHE_DIR lets the worker container keep its cache apart from the web server's (see compose.yaml). */
    public function getCacheDir(): string
    {
        return isset($_SERVER['APP_CACHE_DIR']) ? $_SERVER['APP_CACHE_DIR'].'/'.$this->environment : parent::getCacheDir();
    }

    /**
     * @return list<string> An array of allowed values for APP_ENV
     */
    private function getAllowedEnvs(): array
    {
        return ['prod', 'dev', 'test'];
    }
}
