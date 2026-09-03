# Account balance

[← Back to docs index](README.md)

Scope: `account:read`.

```php
echo $sms->getBalance(); // "123.45"
```

The balance is returned as a **string** to preserve decimal precision — do not
cast it to `float` for money math. If you need to compare or subtract, use a
decimal-safe approach:

```php
if (bccomp($sms->getBalance(), '0.30', 2) === -1) {
    // not enough for a 0.30 MDL segment
}
```

The currency is always `MDL`.

## See also

- [`InsufficientBalanceException`](errors.md) — thrown on `402` when a send
  can't be covered
- [Estimate cost](messages.md#estimate-cost) before sending
