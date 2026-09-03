# Legacy v1 API

[← Back to docs index](README.md)

> **Deprecated.** The v1 endpoints are kept only for backward compatibility.
> New code should use the [v3 methods](messages.md) on `SmsMd`. v1 authenticates
> with `?token=` and returns flat (un-enveloped) payloads.

Reach the legacy client via `->legacy()`:

```php
$legacy = $sms->legacy();
```

## Send

```php
$legacy->send(
    to:      '69123456',
    from:    'sms.md',
    message: 'Hello',
    time:    null, // 'Y-m-d H:i:s' to schedule, null = now
);
```

## Messages

```php
$legacy->messages(page: 1, dateFrom: '2026-07-01', dateTo: '2026-07-20');
$legacy->message('MESSAGE_ID');
$legacy->statuses();
```

## Balance

```php
echo $legacy->balance(); // "150.00" (string)
```

## Errors

Legacy calls still throw the same [typed exceptions](errors.md); the SDK maps
the flat v1 error shape onto the standard hierarchy by HTTP status.

## See also

- [Migration from 1.x](migration.md)
