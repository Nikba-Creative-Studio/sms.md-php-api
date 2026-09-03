# Messages

[← Back to docs index](README.md)

Scopes: sending needs `messages:send`; reading needs `messages:read`.

## Send a message

```php
$data = $sms->sendMessage(
    to:   '69123456',
    text: 'Hello World!',
    from: 'sms.md',
);
```

Returned `data` includes: `id`, `to`, `text` (after normalization),
`characters`, `segments`, `encoding` (`gsm-7` / `ucs-2`), `cost`, `currency`,
`destination`, `countryIso`, `scheduledAt`.

### Number format

National with no prefix is recommended: `69123456`. Also accepted:
`069123456`, `37369123456`, `+37369123456`, and forms with spaces or dashes.
Valid Moldovan prefixes: 60, 61, 62, 67, 68, 69, 76, 78, 79, 80. Foreign
numbers are [international](international.md).

### Text length & encoding

Up to **800 characters**. A single Cyrillic character or emoji switches the
whole message to UCS-2 (70 chars/segment instead of 160). Use
[`estimate()`](#estimate-cost) to preview segments and cost.

## Schedule a message

Pass `sendAt` as `Y-m-d H:i:s` in **Europe/Chisinau** local time (no timezone in
the string). Must be in the future, at most 30 days ahead:

```php
$sms->sendMessage('69123456', 'Reminder', 'sms.md', sendAt: '2026-08-07 14:30:00');
```

The ISO variant with `T` (`2026-08-07T14:30:00`) is also accepted.

## Estimate cost

Preview segments and price **without sending**:

```php
$e = $sms->estimate('69123456', 'Мама мыла раму');
$e['segments'];    // number of SMS
$e['encoding'];    // "ucs-2"
$e['cost'];        // total, as a string
$e['destination']; // "moldova" | "international"
```

## List messages

Returns `['data' => [...], 'meta' => [...]]`; `meta` carries pagination.

```php
use Nikba\SmsMdPhpApi\Enum\MessageStatus;

$page = $sms->listMessages(['status' => MessageStatus::Delivered], page: 1);

foreach ($page['data'] as $message) {
    // ...
}
$page['meta']; // currentPage, total, ...
```

### Filters

| Key | Meaning |
|---|---|
| `dateCreated[after]` / `dateCreated[before]` | Creation time bounds (date or datetime) |
| `dateSent[after]` / `dateSent[before]` | Actual send-time bounds |
| `senderName` | Exact sender name |
| `receiverNumber` | Recipient number as stored by the platform |
| `status` | `int` or [`MessageStatus`](#status-reference) |
| `order[desc]` | `dateCreated` |

```php
$sms->listMessages([
    'dateCreated[after]'  => '2026-08-01',
    'dateCreated[before]' => '2026-08-31',
    'senderName'          => 'sms.md',
], page: 1);
```

## Fetch one message

```php
$sms->getMessage('449d5410-82d3-4b6e-96bc-cc92a33eb3f5');
```

## Status reference

The numeric ids used by the API, mirrored by the `MessageStatus` enum:

| id | Enum case | Meaning |
|---|---|---|
| 1 | `Pending` | Waiting to be sent (scheduled) |
| 2 | `Sent` | Handed to the operator |
| 3 | `Delivered` | Delivered by the operator |
| 4 | `Resending` | Retry after a send error |
| 5 | `Queued` | In the operator's queue |
| 9 | `Failed` | Not sent by the operator |

```php
$sms->messageStatuses(); // full id → name/description list from the API
```

## See also

- [Bulk sending](bulk.md)
- [International sending](international.md)
- [Error handling](errors.md)
