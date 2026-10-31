<?php

namespace App\Http;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpClient\DecoratorTrait;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/** Refuses requests once the monthly quota of the streaming API is used up (the API blocks us for the rest of the month otherwise). */
#[AsDecorator('streaming.client', priority: 10)]
final class BudgetHttpClient implements HttpClientInterface
{
    use DecoratorTrait;

    public function __construct(
        #[AutowireDecorated]
        HttpClientInterface $client,
        #[Target('streaming_api')]
        private readonly RateLimiterFactoryInterface $limiter,
        #[Autowire(env: 'MOTN_API_KEY')]
        private readonly string $apiKey,
    ) {
        $this->client = $client;
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if ('' === $this->apiKey) {
            throw new TransportException('No MOTN_API_KEY configured.');
        }
        if (!$this->limiter->create('month-'.date('Y-m'))->consume()->isAccepted()) {
            throw new TransportException('Monthly budget of the streaming API is used up.');
        }

        return $this->client->request($method, $url, $options);
    }
}
