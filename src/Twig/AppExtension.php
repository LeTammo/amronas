<?php

namespace App\Twig;

use App\Streaming\ServiceCatalog;
use Twig\Attribute\AsTwigFilter;
use Twig\Attribute\AsTwigFunction;

final class AppExtension
{
    /** IMDb images can be resized on the fly: "…_V1_.jpg" → "…_V1_QL75_UX120_.jpg". */
    #[AsTwigFilter('poster')]
    public static function poster(?string $url, int $width = 120): ?string
    {
        if (!$url || !str_contains($url, 'media-amazon.com')) {
            return $url;
        }

        return preg_replace('/\._V1_.*\.(jpg|png)$/', \sprintf('._V1_QL75_UX%d_.$1', $width), $url);
    }

    /** 127 → "2 h 07 min" */
    #[AsTwigFilter('runtime')]
    public static function runtime(?int $minutes): ?string
    {
        if (null === $minutes) {
            return null;
        }

        return $minutes < 60 ? $minutes.' min' : \sprintf('%d h %02d min', intdiv($minutes, 60), $minutes % 60);
    }

    #[AsTwigFunction('service_short')]
    public static function serviceShort(string $service): string
    {
        return ServiceCatalog::short($service);
    }

    #[AsTwigFunction('service_color')]
    public static function serviceColor(string $service, ?string $fallback = null): string
    {
        return ServiceCatalog::color($service, $fallback);
    }
}
