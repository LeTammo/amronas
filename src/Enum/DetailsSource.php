<?php

namespace App\Enum;

/** Where a title's genres, rating and seasons come from: the Streaming Availability API, or a fallback if it doesn't know the title. */
enum DetailsSource: string
{
    case Streaming = 'motn';
    case Imdb = 'omdb';
    case Tmdb = 'tmdb';
}
