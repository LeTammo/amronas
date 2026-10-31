<?php

namespace App\Http;

use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Saves API responses to files and replays them, so development and tests don't use up API quotas.
 *
 * Modes: "off" (pass through), "replay" (saved responses only, 404 otherwise), "record" (replay, else call the API and save successful responses).
 */
final class RecordingHttpClient implements HttpClientInterface
{
    use DecoratorTrait;

    public function __construct(
        HttpClientInterface $client,
        private readonly string $mode,
        private readonly string $directory,
        private readonly string $baseUri,
    ) {
        $this->client = $client;
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if ('off' === $this->mode) {
            return $this->client->request($method, $url, $options);
        }

        $file = $this->fileFor($method, $url, $options);
        if (is_file($file)) {
            $saved = json_decode(file_get_contents($file), true, flags: \JSON_THROW_ON_ERROR);

            return $this->mock($method, $url, $options, new MockResponse(
                json_encode($saved['body'], \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE),
                ['http_code' => $saved['status'], 'response_headers' => ['content-type' => 'application/json']],
            ));
        }

        if ('replay' === $this->mode) {
            return $this->mock($method, $url, $options, new MockResponse('{"error":"no recording"}', ['http_code' => 404]));
        }

        $response = $this->client->request($method, $url, $options);
        if (200 === $response->getStatusCode()) {
            if (!is_dir(\dirname($file))) {
                mkdir(\dirname($file), 0o775, true);
            }
            file_put_contents($file, json_encode([
                'request' => $method.' '.$url.($options['query'] ?? null ? '?'.http_build_query($options['query']) : ''),
                'recordedAt' => date(\DATE_ATOM),
                'status' => 200,
                'body' => $response->toArray(false),
            ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));
        }

        return $response;
    }

    private function mock(string $method, string $url, array $options, MockResponse $response): ResponseInterface
    {
        unset($options['base_uri']);

        return (new MockHttpClient($response, $this->baseUri))->request($method, $url, $options);
    }

    private function fileFor(string $method, string $url, array $options): string
    {
        $path = parse_url($url, \PHP_URL_PATH) ?? $url;
        $query = $options['query'] ?? [];
        ksort($query);
        $slug = trim(preg_replace('/[^a-z0-9]+/i', '-', $path.'-'.http_build_query($query)), '-');

        return \sprintf('%s/%s/%s.json', $this->directory, parse_url($this->baseUri, \PHP_URL_HOST), strtolower($method.'-'.substr($slug, 0, 120)));
    }
}
