# Error handling

[← Back to docs index](README.md)

Every failed request throws an exception. **Branch on the machine-readable
code, never on the message** — in production the API replaces the message with a
generic string ("An error occurred.").

## Exception hierarchy

All exceptions extend `Nikba\SmsMdPhpApi\Exception\SmsMdException`, so you can
catch that to handle any SDK failure at once.

```
SmsMdException
├── TransportException        network failure / non-JSON response (no HTTP code)
└── ApiException              any API error envelope
    ├── AuthenticationException      401  AUTHENTICATION_REQUIRED, INVALID_API_TOKEN
    ├── InsufficientBalanceException 402  INSUFFICIENT_BALANCE
    ├── ForbiddenException           403  FORBIDDEN, SCOPE_FORBIDDEN
    ├── NotFoundException            404  NOT_FOUND
    ├── ValidationException          422  VALIDATION_ERROR
    ├── RateLimitException           429  RATE_LIMIT_EXCEEDED
    └── ServerException              5xx  INTERNAL_ERROR
```

## Inspecting an error

```php
use Nikba\SmsMdPhpApi\Exception\ApiException;

try {
    $sms->sendMessage('69123456', 'Hi', 'sms.md');
} catch (ApiException $e) {
    $e->getErrorCode(); // "RATE_LIMIT_EXCEEDED"
    $e->getHttpCode();  // 429
    $e->getMessage();   // generic in production — don't branch on it
}
```

## Catching specific cases

```php
use Nikba\SmsMdPhpApi\Exception\InsufficientBalanceException;
use Nikba\SmsMdPhpApi\Exception\ValidationException;
use Nikba\SmsMdPhpApi\Exception\ApiException;
use Nikba\SmsMdPhpApi\Exception\TransportException;

try {
    $sms->sendMessage('69123456', 'Hi', 'sms.md');
} catch (ValidationException $e) {
    // per-field messages
    $e->getErrors();        // ['to' => ['The number is invalid.']]
    $e->firstError('to');   // 'The number is invalid.'
    $e->firstError('_');    // errors not tied to a field (e.g. international disabled)
} catch (InsufficientBalanceException $e) {
    // top up the account
} catch (TransportException $e) {
    // network/timeout/undecodable — safe to retry with backoff
} catch (ApiException $e) {
    // anything else from the API
}
```

## Validation errors

`ValidationException::getErrors()` returns `array<string, string[]>`: the key is
the request field name, the value is a list of messages. The special `_` key
holds errors not tied to a specific field.

```php
[
    'to'   => ['The number is invalid.'],
    '_'    => ['International numbers are not supported for your account.'],
]
```

## Scopes

Each API token is created with a set of **scopes**. Calling an endpoint the
token's scopes don't cover fails with `403 SCOPE_FORBIDDEN`
([`ForbiddenException`](#exception-hierarchy)).

| Scope | Grants |
|---|---|
| `messages:send` | `sendMessage`, `sendBulk`, `estimate` |
| `messages:read` | `listMessages`, `getMessage`, `getBulkMessages`, `messageStatuses` |
| `otp:send` | `sendOtp`, `verifyOtp` |
| `contacts:read` | `listAddressBooks`, `getAddressBook`, `listAddressBookContacts` |
| `contacts:write` | `createAddressBook`, `updateAddressBook`, `deleteAddressBook`, `importContacts`, `deleteContact` |
| `senders:read` | `listSenderAliases`, `getSenderAlias` |
| `account:read` | `getBalance` |

There is no "grant everything" scope — pick per token in **Settings → API**.

## See also

- [Getting started](getting-started.md)
- [International sending](international.md) — the `_` / `phone` key cases
