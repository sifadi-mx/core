<?php

declare(strict_types=1);

namespace Sifadi\Core\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use Sifadi\Core\Exceptions\ApiException;
use Sifadi\Core\Exceptions\ConnectionException;

/**
 * The transport that signs every request with the bearer token, talks to the
 * Sifadi API over HTTP and turns error statuses into typed exceptions.
 *
 * Framework-agnostic: it uses Guzzle by default, but you can inject any
 * pre-configured Guzzle client through the `client` option (handy for tests).
 */
final class HttpClient
{
    private const VERSION = '1.0.0';

    private Client $client;

    private int $timeout;

    private string $userAgent;

    /**
     * @param  array{client?: Client, timeout?: int, user_agent?: string}  $options
     */
    public function __construct(
        private readonly string $token,
        private readonly string $baseUrl,
        array $options = [],
    ) {
        $this->timeout = $options['timeout'] ?? 30;
        $this->userAgent = $options['user_agent'] ?? 'sifadi-core-php/'.self::VERSION;
        $this->client = $options['client'] ?? new Client([
            'timeout' => $this->timeout,
            'http_errors' => false,
        ]);
    }

    /**
     * @param  array<string, scalar|null>  $query
     * @param  array<string, string>  $headers
     */
    public function get(string $path, array $query = [], array $headers = []): Response
    {
        return $this->send('GET', $path, ['query' => array_filter($query, static fn ($v) => $v !== null)], $headers);
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, string>  $headers
     */
    public function post(string $path, array $body = [], array $headers = []): Response
    {
        return $this->send('POST', $path, ['json' => $body === [] ? new \stdClass : $body], $headers);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, string>  $headers
     */
    private function send(string $method, string $path, array $payload, array $headers): Response
    {
        $options = $payload + [
            'headers' => [
                'Authorization' => 'Bearer '.$this->token,
                'Accept' => 'application/json',
                'User-Agent' => $this->userAgent,
            ] + $headers,
        ];

        try {
            $raw = $this->client->request($method, $this->url($path), $options);
        } catch (ConnectException $e) {
            throw new ConnectionException('No se pudo conectar con la API de Sifadi: '.$e->getMessage(), 0, $e);
        } catch (GuzzleException $e) {
            throw new ConnectionException('Error de transporte al llamar a la API de Sifadi: '.$e->getMessage(), 0, $e);
        }

        $headersOut = [];
        foreach ($raw->getHeaders() as $name => $values) {
            $headersOut[$name] = $values[0] ?? '';
        }

        $response = new Response($raw->getStatusCode(), (string) $raw->getBody(), $headersOut);

        if (! $response->successful()) {
            throw ApiException::fromResponse($response);
        }

        return $response;
    }

    private function url(string $path): string
    {
        return $this->baseUrl.'/'.ltrim($path, '/');
    }
}
