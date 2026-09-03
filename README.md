# sms.md PHP SDK

[![Latest Version](https://img.shields.io/packagist/v/nikba/sms.md-php-api.svg?style=flat-square)](https://packagist.org/packages/nikba/sms.md-php-api)
[![Tests](https://img.shields.io/github/actions/workflow/status/Nikba-Creative-Studio/sms.md-php-api/tests.yml?branch=main&style=flat-square&label=tests)](https://github.com/Nikba-Creative-Studio/sms.md-php-api/actions/workflows/tests.yml)
[![PHP Version](https://img.shields.io/packagist/php-v/nikba/sms.md-php-api.svg?style=flat-square)](https://packagist.org/packages/nikba/sms.md-php-api)
[![Total Downloads](https://img.shields.io/packagist/dt/nikba/sms.md-php-api.svg?style=flat-square)](https://packagist.org/packages/nikba/sms.md-php-api)
[![License](https://img.shields.io/packagist/l/nikba/sms.md-php-api.svg?style=flat-square)](LICENSE)

Modern PHP SDK for the [sms.md](https://sms.md/) **v3 Client API** — send single, bulk and one‑time‑code (OTP) SMS in Moldova, estimate cost, read delivery history, manage address books and check your balance.

- PHP **8.1+**, `declare(strict_types=1)` throughout
- **PSR‑18** HTTP client (bring your own, or auto‑discovered)
- Typed **exceptions** you can branch on, plus **enums** for statuses and encoding
- Targets the current **v3** API; the deprecated **v1** endpoints remain available via `->legacy()`

📖 **[Full documentation](docs/README.md)** · [API reference](https://docs.sms.md/) · [Changelog](CHANGELOG.md)

> **Upgrading from 1.x?** The v1 API this SDK used is now legacy. 2.0 is a rewrite around v3 with new method names and exception‑based error handling. See [Migration](docs/migration.md).

## Installation

```bash
composer require nikba/sms.md-php-api
```

The SDK talks to any [PSR‑18](https://www.php-fig.org/psr/psr-18/) HTTP client. If you don't already have one, install Guzzle (it will be discovered automatically):

```bash
composer require guzzlehttp/guzzle
```

## Quick start

1. Create an API token in your account: **Settings → API** (`https://app.sms.md/settings/api`).
2. Register and get approval for a sender name: **Settings → Sender names**.
3. Send:

```php
use Nikba\SmsMdPhpApi\SmsMd;

$sms = new SmsMd('YOUR_API_TOKEN');

$result = $sms->sendMessage(
    to:   '69123456',
    text: 'Your code is 1234',
    from: 'sms.md',
);

echo $result['id'];       // message UUID
echo $result['segments']; // billed segments
echo $result['cost'];     // e.g. "0.30"
```

> A token grants full access to the account and can spend your balance — keep it **server‑side**, never in mobile apps or frontend code.

## Usage

### Send an SMS

```php
$sms->sendMessage('69123456', 'Hello World!', 'sms.md');

// Scheduled (Europe/Chisinau local time, up to 30 days ahead):
$sms->sendMessage('69123456', 'Reminder', 'sms.md', sendAt: '2026-08-07 14:30:00');
```

Recommended number format is national with no prefix (`69123456`). `069123456`, `37369123456`, `+37369123456` and forms with spaces/dashes are accepted too.

### Bulk send (up to 500 recipients)

```php
$sms->sendBulk(['69123456', '078123456', '37378123456'], '20% off, today only', 'sms.md');
```

### Estimate cost before sending

```php
$e = $sms->estimate('69123456', 'Мама мыла раму'); // Cyrillic → UCS-2
echo $e['segments'];  // number of SMS
echo $e['encoding'];  // "gsm-7" | "ucs-2"
echo $e['cost'];      // total, as a string
```

### One‑time codes (OTP)

```php
// Send a code through an OTP application (Settings → OTP):
$sms->sendOtp('69123456', 'YOUR_OTP_APP_KEY', action: 'login');

// Verify what the user entered — returns true, or throws on a wrong/expired code:
try {
    $sms->verifyOtp('69123456', '482913', 'YOUR_OTP_APP_KEY', action: 'login');
    // ok
} catch (\Nikba\SmsMdPhpApi\Exception\ApiException $e) {
    // invalid or expired
}
```

### Read messages

```php
use Nikba\SmsMdPhpApi\Enum\MessageStatus;

$page = $sms->listMessages(['status' => MessageStatus::Delivered], page: 1);
foreach ($page['data'] as $message) { /* ... */ }
$page['meta']; // pagination info

$sms->getMessage('449d5410-82d3-4b6e-96bc-cc92a33eb3f5');
$sms->getBulkMessages('BULK_ID');
$sms->messageStatuses(); // status id → name reference
```

Supported `listMessages` filters: `dateCreated[after]`, `dateCreated[before]`, `dateSent[after]`, `dateSent[before]`, `senderName`, `receiverNumber`, `status`, `order[desc]`.

### Sender names

```php
$sms->listSenderAliases();
$sms->getSenderAlias('ID');
```

### Address books & contacts

```php
$book = $sms->createAddressBook('Newsletter subscribers', 'From the website form');
$sms->updateAddressBook($book['id'], 'Newsletter');
$sms->listAddressBooks();
$sms->getAddressBook('ID');

$sms->importContacts('BOOK_ID', "69123456,Ion,Popescu\n69654321\n", ['deduplicate' => true]);
$sms->listAddressBookContacts('BOOK_ID', ['firstName' => 'Ion']);
$sms->deleteContact('CONTACT_ID');
$sms->deleteAddressBook('BOOK_ID');
```

### International sending

There is **no separate method or parameter** for foreign numbers — pass an international number to the same `sendMessage()` / `sendBulk()` / `estimate()` calls and the API detects the destination automatically. `+373 77…` (Transnistria) and any non‑Moldovan number count as international.

International sending must be **enabled for your account** (and may be restricted to specific countries). The response tells you the destination and country:

```php
use Nikba\SmsMdPhpApi\Enum\Destination;

$r = $sms->sendMessage('+40712345678', 'Salut', 'sms.md');

Destination::from($r['destination']); // Destination::International
$r['countryIso'];   // "RO"
$r['countryName'];  // "Romania"
$r['cost'];         // provider price, depends on the country
```

If international sending is **disabled**, a single send throws `ValidationException` with the reason under the `_` key (not tied to a field):

```php
try {
    $sms->sendMessage('+40712345678', 'Salut', 'sms.md');
} catch (\Nikba\SmsMdPhpApi\Exception\ValidationException $e) {
    echo $e->firstError('_'); // "…International numbers are not supported for your account."
}
```

In a **bulk** send, disallowed numbers are skipped individually rather than failing the whole batch — the rest still go out:

```php
$r = $sms->sendBulk(['69123456', '+1202555000'], 'Hi', 'sms.md');
$r['queued'];    // how many were actually sent
$r['rejected'];  // numbers skipped because their country isn't allowed
```

Use `estimate()` first to see the segment count and per‑country price without sending.

### Balance

```php
echo $sms->getBalance(); // "123.45" (string, to preserve precision)
```

## Error handling

Every failed request throws a typed exception. **Branch on the machine‑readable code, never on the message** — in production the message is replaced with a generic string.

```php
use Nikba\SmsMdPhpApi\Exception\ApiException;
use Nikba\SmsMdPhpApi\Exception\ValidationException;
use Nikba\SmsMdPhpApi\Exception\InsufficientBalanceException;

try {
    $sms->sendMessage('69123456', 'Hi', 'sms.md');
} catch (ValidationException $e) {
    $e->getErrors();          // ['to' => ['The number is invalid.']]
    $e->firstError('to');
} catch (InsufficientBalanceException $e) {
    // top up the account
} catch (ApiException $e) {
    $e->getErrorCode();       // e.g. RATE_LIMIT_EXCEEDED
    $e->getHttpCode();        // 429
}
```

| Exception | Error code(s) | HTTP |
|---|---|---|
| `AuthenticationException` | `AUTHENTICATION_REQUIRED`, `INVALID_API_TOKEN` | 401 |
| `InsufficientBalanceException` | `INSUFFICIENT_BALANCE` | 402 |
| `ForbiddenException` | `FORBIDDEN`, `SCOPE_FORBIDDEN` | 403 |
| `NotFoundException` | `NOT_FOUND` | 404 |
| `ValidationException` | `VALIDATION_ERROR` | 422 |
| `RateLimitException` | `RATE_LIMIT_EXCEEDED` | 429 |
| `ServerException` | `INTERNAL_ERROR` | 5xx |
| `TransportException` | — (network / non‑JSON response) | — |

All of the above extend `SmsMdException`, so you can catch that to handle any SDK failure at once.

## Scopes

Each API token is created with a set of scopes; an endpoint whose scope the token lacks fails with `403 SCOPE_FORBIDDEN`. Relevant scopes: `messages:send`, `messages:read`, `otp:send`, `contacts:read`, `contacts:write`, `senders:read`, `account:read`.

## Billing

You're charged **per segment**. A single Cyrillic character switches the whole message to UCS‑2 (70 chars/segment instead of 160). Use `estimate()` or the `Encoding` enum to reason about segment sizes:

```php
use Nikba\SmsMdPhpApi\Enum\Encoding;

Encoding::Gsm7->singleSegmentLength(); // 160
Encoding::Ucs2->singleSegmentLength(); // 70
```

## Injecting your own HTTP client

Any PSR‑18 client and PSR‑17 factories can be passed explicitly — useful for custom timeouts, retries, proxies or testing:

```php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;

$guzzle  = new Client(['timeout' => 10]);
$factory = new HttpFactory();

$sms = new SmsMd('YOUR_API_TOKEN', $guzzle, $factory, $factory);
```

## Legacy v1 API

The deprecated v1 endpoints remain reachable for backward compatibility:

```php
$sms->legacy()->send('69123456', 'sms.md', 'Hello');
$sms->legacy()->balance();
$sms->legacy()->messages(page: 1, dateFrom: '2026-07-01', dateTo: '2026-07-20');
$sms->legacy()->message('MESSAGE_ID');
$sms->legacy()->statuses();
```

## Migration from 1.x

| 1.x | 2.0 |
|---|---|
| `$sms->sendSms($to, $msg, $from)` | `$sms->sendMessage($to, $text, $from)` |
| `$sms->getBalance()` → `int` | `$sms->getBalance()` → `string` |
| `$sms->getMessages($page, $from, $to, $status)` | `$sms->listMessages($filters, $page)` (returns `data` + `meta`) |
| `$sms->getMessage($id)` | `$sms->getMessage($id)` |
| `$sms->getMessageStatuses()` | `$sms->messageStatuses()` |
| `$sms->getSenderAliases()` | `$sms->listSenderAliases()` |
| `$sms->getContacts()` / `getAddressBooks()` | `$sms->listAddressBookContacts()` / `listAddressBooks()` |
| errors returned in the payload | typed exceptions thrown |

Auth moved from a `?token=` query parameter to the `X-Api-Token` header, and sends are now `POST … /v3/messages` with a JSON body. Responses are wrapped in a `{status, data, meta}` envelope, which the SDK unwraps for you.

## Testing

```bash
composer install
composer test
```

## License

MIT — **Free Software, Hell Yeah!**
