# Sender names

[← Back to docs index](README.md)

Scope: `senders:read`.

A **sender name** (sender alias) is the `from` shown to the recipient. It must
be registered and approved in your account (**Settings → Sender names**) before
you can send with it — sending with an unregistered name fails with a
[`ValidationException`](errors.md) (`422`).

## List your sender names

Returns `['data' => [...], 'meta' => [...]]`.

```php
$page = $sms->listSenderAliases(page: 1);

foreach ($page['data'] as $alias) {
    // $alias['name'], approval status, ...
}
```

## Fetch one

```php
$sms->getSenderAlias('SENDER_ALIAS_ID');
```

## See also

- [Messages](messages.md) — the `from` argument
- [Error handling](errors.md)
