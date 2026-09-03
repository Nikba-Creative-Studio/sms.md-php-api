# Migration from 1.x

[← Back to docs index](README.md)

Version 2.0 is a rewrite around the **v3 API**. The old v1 API this SDK used is
now legacy. This guide lists what changed and how to update your code.

## What changed at a glance

- **Auth** moved from a `?token=` query parameter to the `X-Api-Token` header.
- **Sending** is now `POST /v3/messages` with a JSON body (was `GET /v1/send`).
- **Responses** are wrapped in a `{status, data, meta}` envelope, which the SDK
  unwraps for you — methods return the `data` payload (list methods return
  `['data' => ..., 'meta' => ...]`).
- **Errors** are now thrown as [typed exceptions](errors.md) instead of being
  returned inside the payload.
- **PHP 8.1+** is required; Guzzle is now optional (any PSR-18 client works).

## Method mapping

| 1.x | 2.0 |
|---|---|
| `sendSms($to, $msg, $from)` | `sendMessage($to, $text, $from)` |
| `getBalance()` → `int` | `getBalance()` → `string` |
| `getMessages($page, $from, $to, $status)` | `listMessages($filters, $page)` → `data` + `meta` |
| `getMessage($id)` | `getMessage($id)` |
| `getMessageStatuses()` | `messageStatuses()` |
| `getSenderAliases()` | `listSenderAliases()` |
| `getContacts($page)` | `listAddressBookContacts($bookId, $filters, $page)` |
| `getAddressBooks($page)` | `listAddressBooks($page)` |
| `getAddressBookContacts($id, $page)` | `listAddressBookContacts($id, $filters, $page)` |
| `getStats()` | *(removed — no v3 equivalent)* |

## Before / after

**1.x**

```php
$sms = new \Nikba\SmsMdPhpApi\SmsMd('TOKEN');
$response = $sms->sendSms('37360820825', 'Hello', 'Nikba');
if (isset($response['error'])) {
    // handle error from the payload
}
$balance = $sms->getBalance(); // int
```

**2.0**

```php
use Nikba\SmsMdPhpApi\SmsMd;
use Nikba\SmsMdPhpApi\Exception\ApiException;

$sms = new SmsMd('TOKEN');

try {
    $data = $sms->sendMessage('69123456', 'Hello', 'Nikba');
    // $data['id'], $data['cost'], ...
} catch (ApiException $e) {
    // handle by $e->getErrorCode()
}

$balance = $sms->getBalance(); // string, e.g. "123.45"
```

## Staying on v1 temporarily

If you can't migrate the sending logic yet, the legacy endpoints remain
available — see [Legacy v1 API](legacy.md):

```php
$sms->legacy()->send('69123456', 'sms.md', 'Hello');
```

Note this still calls the deprecated v1 endpoints; prefer migrating to the v3
methods above.
