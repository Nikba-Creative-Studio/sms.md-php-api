<?php

declare(strict_types=1);

namespace Nikba\SmsMdPhpApi;

use Nikba\SmsMdPhpApi\Http\Transport;

/**
 * Thin wrapper over the deprecated sms.md **v1** endpoints.
 *
 * Kept only for backward compatibility; new code should use the v3 methods on
 * {@see SmsMd}. Reached via {@see SmsMd::legacy()}.
 *
 * v1 authenticates with `?token=` and returns flat (un-enveloped) payloads.
 */
final class LegacyClient
{
    public function __construct(private readonly Transport $transport)
    {
    }

    /**
     * Send an SMS via `GET /v1/send`.
     *
     * @param string|null $time Schedule time `Y-m-d H:i:s`; null = send now.
     * @return array<string,mixed> Flat legacy payload (id, statusId, dateCreate, …).
     */
    public function send(string $to, string $from, string $message, ?string $time = null): array
    {
        return $this->transport->requestV1('GET', '/v1/send', array_filter([
            'to'      => $to,
            'from'    => $from,
            'message' => $message,
            'time'    => $time,
        ], static fn ($v) => $v !== null));
    }

    /**
     * List messages via `GET /v1/message`.
     *
     * @return array<string,mixed>
     */
    public function messages(int $page = 1, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        return $this->transport->requestV1('GET', '/v1/message', array_filter([
            'page'      => $page,
            'date_from' => $dateFrom,
            'date_to'   => $dateTo,
        ], static fn ($v) => $v !== null));
    }

    /**
     * Fetch a message by id via `GET /v1/message/{id}`.
     *
     * @return array<string,mixed>
     */
    public function message(string $id): array
    {
        return $this->transport->requestV1('GET', '/v1/message/' . rawurlencode($id));
    }

    /**
     * The reference list of statuses via `GET /v1/message/status`.
     *
     * @return array<int,array<string,mixed>>
     */
    public function statuses(): array
    {
        /** @var array<int,array<string,mixed>> */
        return $this->transport->requestV1('GET', '/v1/message/status');
    }

    /**
     * Account balance as a string via `GET /v1/balance`.
     */
    public function balance(): string
    {
        $body = $this->transport->requestV1('GET', '/v1/balance');

        return (string) ($body['balance'] ?? '0');
    }
}
