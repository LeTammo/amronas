<?php

namespace App\Service;

class GoogleService
{
    public function getBestSearchResult(string $name, int $year): string
    {
        $query = str_replace(" ", "+", $name);
        $url = sprintf("https://www.youtube.com/results?search_query=%s+%s+official+trailer+english", $query, $year);
        $response = file_get_contents($url);

        $first = "var ytInitialData = ";
        $response = substr($response, stripos($response, $first) + strlen($first), strlen($response));
        $second = ";</script>";
        $response = substr($response, 0, stripos($response, $second));

        $searchResults = json_decode($response, true);
        $bestResult = $searchResults['contents']
                                    ['twoColumnSearchResultsRenderer']
                                    ['primaryContents']
                                    ['sectionListRenderer']
                                    ['contents'][0]
                                    ['itemSectionRenderer']
                                    ['contents'][0]
                                    ['videoRenderer']
                                    ['navigationEndpoint']
                                    ['commandMetadata']
                                    ['webCommandMetadata']
                                    ['url'];

        return str_replace("/watch?v=", "", $bestResult);
    }
}