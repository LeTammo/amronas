<?php

namespace App\Streaming;

/**
 * Streaming services and countries users can choose from in their settings.
 * Ids follow the Streaming Availability API (https://docs.movieofthenight.com/guide/countries-and-services).
 * Services that API doesn't know come from TMDB's watch providers instead (see TMDB_PROVIDERS).
 */
final class ServiceCatalog
{
    private const NEUTRAL = '#82766F';

    /** @var array<string, array{name: string, color: string, short: string}> short: letters for the small logo */
    public const SERVICES = [
        'netflix' => ['name' => 'Netflix', 'color' => '#E50914', 'short' => 'N'],
        'prime' => ['name' => 'Prime Video', 'color' => '#1A73C9', 'short' => 'PV'],
        'disney' => ['name' => 'Disney+', 'color' => '#1B3A8A', 'short' => 'D+'],
        'wow' => ['name' => 'WOW', 'color' => '#00818A', 'short' => 'W'],
        'apple' => ['name' => 'Apple TV', 'color' => '#3A3A3C', 'short' => 'tv'],
        'hbo' => ['name' => 'HBO Max', 'color' => '#5822B4', 'short' => 'HBO'],
        'paramount' => ['name' => 'Paramount+', 'color' => '#0064FF', 'short' => 'P+'],
        'rtl' => ['name' => 'RTL+', 'color' => '#D6001C', 'short' => 'R+'],
        'crunchyroll' => ['name' => 'Crunchyroll', 'color' => '#F45D00', 'short' => 'CR'],
        'mubi' => ['name' => 'MUBI', 'color' => '#14213D', 'short' => 'MU'],
        'plutotv' => ['name' => 'Pluto TV', 'color' => '#8A7A00', 'short' => 'PL'],
        'curiosity' => ['name' => 'Curiosity Stream', 'color' => '#C88A1E', 'short' => 'CS'],
        'zee5' => ['name' => 'Zee5', 'color' => '#8230C6', 'short' => 'Z5'],
        'ard' => ['name' => 'ARD Mediathek', 'color' => '#003480', 'short' => 'ARD'],
        'zdf' => ['name' => 'ZDF', 'color' => '#E8650E', 'short' => 'ZDF'],
        'arte' => ['name' => 'ARTE', 'color' => '#D9461C', 'short' => 'A'],
        'joyn' => ['name' => 'Joyn', 'color' => '#5C3BE3', 'short' => 'J'],
        'magenta' => ['name' => 'MagentaTV', 'color' => '#E20074', 'short' => 'M'],
        'skygo' => ['name' => 'Sky Go', 'color' => '#0A6BC2', 'short' => 'S'],
        'discovery' => ['name' => 'discovery+', 'color' => '#1F5FC4', 'short' => 'd+'],
        'youtube' => ['name' => 'YouTube', 'color' => '#CC0000', 'short' => 'YT'],
        'rakuten' => ['name' => 'Rakuten TV', 'color' => '#BF0000', 'short' => 'R'],
        'videobuster' => ['name' => 'VIDEOBUSTER', 'color' => '#B3122E', 'short' => 'VB'],
        'maxdome' => ['name' => 'maxdome', 'color' => '#6E8F12', 'short' => 'md'],
    ];

    /**
     * TMDB watch provider ids (https://developer.themoviedb.org/reference/watch-providers-movie-list) of the services
     * the Streaming Availability API doesn't cover. Other TMDB providers are ignored, so services aren't shown twice.
     *
     * @var array<int, array{service: string, name: string}>
     */
    public const TMDB_PROVIDERS = [
        219 => ['service' => 'ard', 'name' => 'ARD Mediathek'],
        537 => ['service' => 'zdf', 'name' => 'ZDF'],
        234 => ['service' => 'arte', 'name' => 'ARTE'],
        304 => ['service' => 'joyn', 'name' => 'Joyn'],
        421 => ['service' => 'joyn', 'name' => 'Joyn PLUS+'],
        178 => ['service' => 'magenta', 'name' => 'MagentaTV'],
        29 => ['service' => 'skygo', 'name' => 'Sky Go'],
        524 => ['service' => 'discovery', 'name' => 'discovery+'],
        192 => ['service' => 'youtube', 'name' => 'YouTube'],
        188 => ['service' => 'youtube', 'name' => 'YouTube Premium'],
        35 => ['service' => 'rakuten', 'name' => 'Rakuten TV'],
        133 => ['service' => 'videobuster', 'name' => 'VIDEOBUSTER'],
        20 => ['service' => 'maxdome', 'name' => 'maxdome'],
    ];

    /**
     * TMDB ids of services the Streaming Availability API covers too. Only their subscriptions are taken from TMDB,
     * because that API sometimes misses one (rent and buy offers come from it with prices and direct links).
     *
     * @var array<int, string>
     */
    public const TMDB_SUBSCRIPTIONS = [
        8 => 'netflix', 1796 => 'netflix', 9 => 'prime', 2100 => 'prime', 613 => 'prime', 337 => 'disney', 30 => 'wow',
        350 => 'apple', 1899 => 'hbo', 531 => 'paramount', 2750 => 'rtl', 283 => 'crunchyroll', 11 => 'mubi',
        300 => 'plutotv', 190 => 'curiosity',
    ];

    public const COUNTRIES = ['de', 'at', 'ch', 'nl', 'gb', 'ie', 'fr', 'it', 'es', 'us', 'ca'];

    /** Our own colours win; very light API colours (white text wouldn't be readable) fall back to a neutral tone. */
    public static function color(string $service, ?string $fallback = null): string
    {
        $color = self::SERVICES[$service]['color'] ?? $fallback;
        if (!$color || !preg_match('/^#([0-9a-f]{6})$/i', $color, $m)) {
            return self::NEUTRAL;
        }
        [$r, $g, $b] = array_map(hexdec(...), str_split($m[1], 2));

        return (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255 > 0.7 ? self::NEUTRAL : $color;
    }

    /** Letters for the small logo: "PV" for Prime Video */
    public static function short(string $service): string
    {
        return self::SERVICES[$service]['short'] ?? mb_strtoupper(mb_substr($service, 0, 2));
    }

    public static function name(string $service): string
    {
        return self::SERVICES[$service]['name'] ?? ucfirst($service);
    }
}
