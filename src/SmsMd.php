<?php

declare(strict_types=1);

namespace Nikba\SmsMdPhpApi;

use Nikba\SmsMdPhpApi\Enum\MessageStatus;
use Nikba\SmsMdPhpApi\Http\Transport;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * PHP SDK for the sms.md v3 Client API.
 *
 * Send single, bulk and one-time-code (OTP) messages, estimate cost, read
 * delivery history, manage address books and check the account balance.
 *
 * All methods return the decoded `data` payload of the response (list methods
 * additionally include the `meta` pagination block). Any API error is thrown
 * as a {@see Exception\ApiException} subclass; branch on
 * {@see Exception\ApiException::getErrorCode()}, never on the message text.
 *
 * The HTTP client is discovered automatically (PSR-18) — install any
 * PSR-18 client such as guzzlehttp/guzzle — or inject your own.
 *
 * @see https://docs.sms.md/ Official API documentation.
 * @author Bargan Nicolai <office@nikba.com>
 */
final class SmsMd
{
    public const VERSION = '2.0.0';

    private const BASE_URI = 'https://api.sms.md';

    private readonly Transport $transport;
    private ?LegacyClient $legacy = null;

    /**
     * @param string $apiToken Token from Settings → API (grants full account access; keep it server-side).
     * @param ClientInterface|null $httpClient A PSR-18 client; auto-discovered when null.
     */
    public function __construct(
        private readonly string $apiToken,
        ?ClientInterface $httpClient = null,
        ?RequestFactoryInterface $requestFactory = null,
        ?StreamFactoryInterface $streamFactory = null,
        string $baseUri = self::BASE_URI,
    ) {
        $this->transport = new Transport($apiToken, rtrim($baseUri, '/'), $httpClient, $requestFactory, $streamFactory);
    }

    // ---------------------------------------------------------------------
    // Messages
    // ---------------------------------------------------------------------

    /**
     * Send an SMS to a single recipient. Scope: `messages:send`.
     *
     * @param string      $to     Recipient number, national format recommended (e.g. `69123456`).
     * @param string      $text   Message body, 1–800 characters.
     * @param string      $from   Registered, approved sender name (max 15 chars).
     * @param string|null $sendAt Schedule time `Y-m-d H:i:s` in Europe/Chisinau; null = send now.
     *
     * @return array<string,mixed> Created message: id, cost, segments, encoding, …
     */
    public function sendMessage(string $to, string $text, string $from, ?string $sendAt = null): array
    {
        return $this->data($this->transport->requestV3('POST', '/v3/messages', [], array_filter([
            'from'   => $from,
            'to'     => $to,
            'text'   => $text,
            'sendAt' => $sendAt,
        ], static fn ($v) => $v !== null)));
    }

    /**
     * Send the same message to up to 500 recipients in one call. Scope: `messages:send`.
     *
     * @param string[]    $recipients 1–500 numbers. Duplicates are billed twice.
     * @param string|null $sendAt     Schedule time `Y-m-d H:i:s` (Europe/Chisinau); null = now.
     *
     * @return array<string,mixed> Bulk result including the bulkId.
     */
    public function sendBulk(array $recipients, string $text, string $from, ?string $sendAt = null): array
    {
        return $this->data($this->transport->requestV3('POST', '/v3/messages/bulk', [], array_filter([
            'from'       => $from,
            'recipients' => array_values($recipients),
            'text'       => $text,
            'sendAt'     => $sendAt,
        ], static fn ($v) => $v !== null)));
    }

    /**
     * Estimate segments and cost of a message without sending it. Scope: `messages:send`.
     *
     * @return array<string,mixed> Segments, encoding, cost, destination, …
     */
    public function estimate(string $to, string $text): array
    {
        return $this->data($this->transport->requestV3('POST', '/v3/messages/estimate', [], [
            'to'   => $to,
            'text' => $text,
        ]));
    }

    /**
     * List messages, newest first. Scope: `messages:read`.
     *
     * Supported filter keys: `dateCreated[after]`, `dateCreated[before]`,
     * `dateSent[after]`, `dateSent[before]`, `senderName`, `receiverNumber`,
     * `status` (int or {@see MessageStatus}), `order[desc]` (`dateCreated`).
     *
     * @param array<string,mixed> $filters
     * @return array{data:array<int,array<string,mixed>>,meta:array<string,mixed>}
     */
    public function listMessages(array $filters = [], int $page = 1): array
    {
        if (isset($filters['status']) && $filters['status'] instanceof MessageStatus) {
            $filters['status'] = $filters['status']->value;
        }

        return $this->page($this->transport->requestV3('GET', '/v3/messages', ['page' => $page] + $filters));
    }

    /**
     * Fetch a single message by its UUID. Scope: `messages:read`.
     *
     * @return array<string,mixed>
     */
    public function getMessage(string $id): array
    {
        return $this->data($this->transport->requestV3('GET', '/v3/messages/' . rawurlencode($id)));
    }

    /**
     * List the individual messages of a bulk send. Scope: `messages:read`.
     *
     * @return array{data:array<int,array<string,mixed>>,meta:array<string,mixed>}
     */
    public function getBulkMessages(string $bulkId, int $page = 1): array
    {
        return $this->page($this->transport->requestV3('GET', '/v3/messages/bulk/' . rawurlencode($bulkId), ['page' => $page]));
    }

    /**
     * The reference list of delivery statuses (id → name/description). Scope: `messages:read`.
     *
     * @return array<int,array<string,mixed>>
     */
    public function messageStatuses(): array
    {
        return $this->data($this->transport->requestV3('GET', '/v3/messages/statuses'));
    }

    // ---------------------------------------------------------------------
    // OTP
    // ---------------------------------------------------------------------

    /**
     * Send a one-time code through an OTP application. Scope: `otp:send`.
     *
     * @param string      $phone       Recipient number.
     * @param string      $apiKey      OTP application key (Settings → OTP), 48 hex chars.
     * @param string|null $action      What the code is for (`login`, `payment`, …); default `default`.
     * @param string|null $referenceId Your own identifier, stored with the request.
     *
     * @return array<string,mixed> Request id and expiry.
     */
    public function sendOtp(string $phone, string $apiKey, ?string $action = null, ?string $referenceId = null): array
    {
        return $this->data($this->transport->requestV3('POST', '/v3/otp/send', [], array_filter([
            'phone'       => $phone,
            'apiKey'      => $apiKey,
            'action'      => $action,
            'referenceId' => $referenceId,
        ], static fn ($v) => $v !== null)));
    }

    /**
     * Verify a one-time code. Scope: `otp:send`. Returns true when the code is
     * valid; an invalid or expired code raises an {@see Exception\ApiException}.
     *
     * @param string|null $action Must match the value used when sending.
     */
    public function verifyOtp(string $phone, string $code, string $apiKey, ?string $action = null): bool
    {
        $this->transport->requestV3('POST', '/v3/otp/verify', [], array_filter([
            'phone'  => $phone,
            'code'   => $code,
            'apiKey' => $apiKey,
            'action' => $action,
        ], static fn ($v) => $v !== null));

        return true;
    }

    // ---------------------------------------------------------------------
    // Sender names
    // ---------------------------------------------------------------------

    /**
     * List your registered sender names. Scope: `senders:read`.
     *
     * @return array{data:array<int,array<string,mixed>>,meta:array<string,mixed>}
     */
    public function listSenderAliases(int $page = 1): array
    {
        return $this->page($this->transport->requestV3('GET', '/v3/sender-aliases', ['page' => $page]));
    }

    /**
     * Fetch a single sender name by id. Scope: `senders:read`.
     *
     * @return array<string,mixed>
     */
    public function getSenderAlias(string $id): array
    {
        return $this->data($this->transport->requestV3('GET', '/v3/sender-aliases/' . rawurlencode($id)));
    }

    // ---------------------------------------------------------------------
    // Address books & contacts
    // ---------------------------------------------------------------------

    /**
     * List address books. Scope: `contacts:read`.
     *
     * @return array{data:array<int,array<string,mixed>>,meta:array<string,mixed>}
     */
    public function listAddressBooks(int $page = 1): array
    {
        return $this->page($this->transport->requestV3('GET', '/v3/address-books', ['page' => $page]));
    }

    /**
     * Fetch a single address book by id. Scope: `contacts:read`.
     *
     * @return array<string,mixed>
     */
    public function getAddressBook(string $id): array
    {
        return $this->data($this->transport->requestV3('GET', '/v3/address-books/' . rawurlencode($id)));
    }

    /**
     * Create an address book. Scope: `contacts:write`.
     *
     * @return array<string,mixed>
     */
    public function createAddressBook(string $name, ?string $description = null): array
    {
        return $this->data($this->transport->requestV3('POST', '/v3/address-books', [], array_filter([
            'name'        => $name,
            'description' => $description,
        ], static fn ($v) => $v !== null)));
    }

    /**
     * Update an address book. Scope: `contacts:write`.
     *
     * @return array<string,mixed>
     */
    public function updateAddressBook(string $id, string $name, ?string $description = null): array
    {
        return $this->data($this->transport->requestV3('PUT', '/v3/address-books/' . rawurlencode($id), [], array_filter([
            'name'        => $name,
            'description' => $description,
        ], static fn ($v) => $v !== null)));
    }

    /** Delete an address book. Scope: `contacts:write`. */
    public function deleteAddressBook(string $id): void
    {
        $this->transport->requestV3('DELETE', '/v3/address-books/' . rawurlencode($id));
    }

    /**
     * List contacts of an address book. Scope: `contacts:read`.
     *
     * Supported filter keys: `phoneNumber`, `firstName`, `lastName`, `status`.
     *
     * @param array<string,mixed> $filters
     * @return array{data:array<int,array<string,mixed>>,meta:array<string,mixed>}
     */
    public function listAddressBookContacts(string $id, array $filters = [], int $page = 1): array
    {
        return $this->page($this->transport->requestV3(
            'GET',
            '/v3/address-books/' . rawurlencode($id) . '/contacts',
            ['page' => $page] + $filters,
        ));
    }

    /**
     * Import contacts into an address book from newline-separated text
     * (`number[,firstName[,lastName[,birthday]]]`). Scope: `contacts:write`.
     *
     * @param array{deduplicate?:bool,onlyNational?:bool,onlyInternational?:bool} $options
     * @return array<string,mixed> Import summary (imported/skipped counts, …).
     */
    public function importContacts(string $id, string $contacts, array $options = []): array
    {
        return $this->data($this->transport->requestV3(
            'POST',
            '/v3/address-books/' . rawurlencode($id) . '/import/list',
            [],
            ['contacts' => $contacts] + $options,
        ));
    }

    /** Delete a contact by id. Scope: `contacts:write`. */
    public function deleteContact(string $id): void
    {
        $this->transport->requestV3('DELETE', '/v3/contacts/' . rawurlencode($id));
    }

    // ---------------------------------------------------------------------
    // Account
    // ---------------------------------------------------------------------

    /**
     * Current account balance as a string (precision preserved). Scope: `account:read`.
     *
     * @return string e.g. `"123.45"`
     */
    public function getBalance(): string
    {
        $data = $this->data($this->transport->requestV3('GET', '/v3/account/balance'));

        return (string) ($data['balance'] ?? '0');
    }

    // ---------------------------------------------------------------------
    // Legacy v1
    // ---------------------------------------------------------------------

    /** Access the legacy v1 endpoints (kept for backward compatibility). */
    public function legacy(): LegacyClient
    {
        return $this->legacy ??= new LegacyClient($this->transport);
    }

    // ---------------------------------------------------------------------
    // Internals
    // ---------------------------------------------------------------------

    /**
     * @param array<string,mixed> $envelope
     * @return array<string,mixed>
     */
    private function data(array $envelope): array
    {
        /** @var array<string,mixed> */
        return $envelope['data'] ?? [];
    }

    /**
     * @param array<string,mixed> $envelope
     * @return array{data:array<int,array<string,mixed>>,meta:array<string,mixed>}
     */
    private function page(array $envelope): array
    {
        return [
            'data' => $envelope['data'] ?? [],
            'meta' => $envelope['meta'] ?? [],
        ];
    }
}
