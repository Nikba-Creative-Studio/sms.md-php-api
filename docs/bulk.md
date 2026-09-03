# Bulk sending

[← Back to docs index](README.md)

Scope: `messages:send`.

Send the same message to up to **500 recipients** in one call.

```php
$data = $sms->sendBulk(
    recipients: ['69123456', '078123456', '37378123456'],
    text:       '20% off, today only',
    from:       'sms.md',
);

$data['bulkId'];   // batch id, use it to fetch the individual messages
$data['queued'];   // how many were actually sent
$data['rejected']; // numbers skipped (country not allowed for the account)
```

## Rules

- 1–500 recipients. Number formats match a single [send](messages.md#number-format).
- **Duplicates are not removed** — a number listed twice receives two messages
  and is billed twice.
- Scheduling works the same as a single send via `sendAt`:

```php
$sms->sendBulk($recipients, 'Reminder', 'sms.md', sendAt: '2026-08-07 14:30:00');
```

## International recipients

If a recipient's country isn't allowed for your account, it is **skipped
individually** — the rest of the batch still goes out — and appears in
`rejected`. See [International sending](international.md).

```php
$data = $sms->sendBulk(['69123456', '+1202555000'], 'Hi', 'sms.md');
$data['queued'];   // 1
$data['rejected']; // ['+1202555000']
```

## Fetch the messages of a bulk

```php
$page = $sms->getBulkMessages($data['bulkId'], page: 1);
foreach ($page['data'] as $message) {
    // per-recipient delivery status
}
$page['meta'];
```

## See also

- [Messages](messages.md)
- [Error handling](errors.md)
