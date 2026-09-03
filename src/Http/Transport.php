<?php

declare(strict_types=1);

namespace Nikba\SmsMdPhpApi\Http;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Nikba\SmsMdPhpApi\Exception\ApiException;
use Nikba\SmsMdPhpApi\Exception\TransportException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Thin transport over any PSR-18 HTTP client: builds requests, sends them,
 * decodes JSON and turns error payloads into typed exceptions.
 *
 * @internal Used by the SDK clients; not part of the public API.
 */
final class Transport
{
    private ClientInterface $httpClient;
    private RequestFactoryInterface $requestFactory;
    private StreamFactoryInterface $streamFactory;

    public function __construct(
        private readonly string $apiToken,
        private readonly string $baseUri,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
    ) {
        $this->httpClient = $httpClient ?? Psr18ClientDiscovery::find();
        $this->requestFactory = $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
    }

    /**
     * Perform a v3 request. Authenticates with the `X-Api-Token` header and
     * interprets the standard success/error envelope.
     *
     * @param array<string,mixed> $query
     * @param array<string,mixed>|null $json
     * @return array<string,mixed> The full decoded envelope (`status`, `data`, optional `meta`).
     *
     * @throws ApiException     On an API error envelope.
     * @throws TransportException On a network failure or an undecodable body.
     */
    public function requestV3(string $method, string $path, array $query = [], ?array $json = null): array
    {
        $request = $this->buildRequest($method, $path, $query, $json)
            ->withHeader('X-Api-Token', $this->apiToken);

        $response = $this->dispatch($request);
        $body = $this->decode($response);

        if (($body['status'] ?? null) === 'error' || $response->getStatusCode() >= 400) {
            throw ApiException::fromCode(
                (string) ($body['code'] ?? 'INTERNAL_ERROR'),
                (string) ($body['message'] ?? 'An error occurred.'),
                (int) ($body['httpCode'] ?? $response->getStatusCode()),
                self::normalizeErrors($body['errors'] ?? []),
            );
        }

        return $body;
    }

    /**
     * Perform a legacy v1 request. Authenticates with the `?token=` query
     * parameter and maps the flat legacy error shape onto an exception.
     *
     * @param array<string,mixed> $query
     * @return array<string,mixed> The decoded response body.
     *
     * @throws ApiException
     * @throws TransportException
     */
    public function requestV1(string $method, string $path, array $query = []): array
    {
        $query['token'] = $this->apiToken;
        $request = $this->buildRequest($method, $path, $query, null);

        $response = $this->dispatch($request);
        $body = $this->decode($response);

        if ($response->getStatusCode() >= 400) {
            throw ApiException::fromCode(
                self::legacyCodeName((int) ($body['code'] ?? $response->getStatusCode())),
                (string) ($body['message'] ?? 'An error occurred.'),
                $response->getStatusCode(),
            );
        }

        return $body;
    }

    /**
     * @param array<string,mixed> $query
     * @param array<string,mixed>|null $json
     */
    private function buildRequest(string $method, string $path, array $query, ?array $json): \Psr\Http\Message\RequestInterface
    {
        $uri = $this->baseUri . $path;
        if ($query !== []) {
            $uri .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $request = $this->requestFactory->createRequest($method, $uri)
            ->withHeader('Accept', 'application/json')
            ->withHeader('User-Agent', 'nikba-sms.md-php-api/2.0');

        if ($json !== null) {
            $encoded = json_encode($json, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            $request = $request
                ->withHeader('Content-Type', 'application/json')
                ->withBody($this->streamFactory->createStream($encoded));
        }

        return $request;
    }

    private function dispatch(\Psr\Http\Message\RequestInterface $request): ResponseInterface
    {
        try {
            return $this->httpClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new TransportException(
                'HTTP request to the sms.md API failed: ' . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /** @return array<string,mixed> */
    private function decode(ResponseInterface $response): array
    {
        $contents = (string) $response->getBody();

        if ($contents === '') {
            return [];
        }

        try {
            /** @var array<string,mixed> $decoded */
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new TransportException(
                'The sms.md API returned a response that is not valid JSON.',
                0,
                $e,
            );
        }

        return \is_array($decoded) ? $decoded : [];
    }

    /**
     * @param mixed $errors
     * @return array<string,string[]>
     */
    private static function normalizeErrors(mixed $errors): array
    {
        if (!\is_array($errors)) {
            return [];
        }

        $normalized = [];
        foreach ($errors as $field => $messages) {
            $normalized[(string) $field] = array_map('strval', (array) $messages);
        }

        return $normalized;
    }

    /** Map a legacy numeric error code onto a v3-style machine code. */
    private static function legacyCodeName(int $httpCode): string
    {
        return match (true) {
            $httpCode === 401 => 'AUTHENTICATION_REQUIRED',
            $httpCode === 402 => 'INSUFFICIENT_BALANCE',
            $httpCode === 403 => 'FORBIDDEN',
            $httpCode === 404 => 'NOT_FOUND',
            $httpCode === 422 => 'VALIDATION_ERROR',
            $httpCode === 429 => 'RATE_LIMIT_EXCEEDED',
            $httpCode >= 500  => 'INTERNAL_ERROR',
            default           => 'VALIDATION_ERROR',
        };
    }
}
