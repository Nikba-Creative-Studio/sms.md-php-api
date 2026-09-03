# International sending

[← Back to docs index](README.md)

## No special method

There is **no separate endpoint or parameter** for foreign numbers. Pass an
international number to the same [`sendMessage()`](messages.md),
[`sendBulk()`](bulk.md) or [`estimate()`](messages.md#estimate-cost) calls and
the platform detects the destination automatically.

`+373 77…` numbers (Transnistria) and any non-Moldovan number count as
international.

## Account must allow it

International sending only works if it is **enabled for your account**, and it
may be restricted to specific countries. This is an account setting on the
sms.md side, not something the SDK controls.

## Reading the destination

Send and estimate responses tell you the destination, country and price:

```php
use Nikba\SmsMdPhpApi\Enum\Destination;

$r = $sms->sendMessage('+40712345678', 'Salut', 'sms.md');

Destination::from($r['destination']); // Destination::International
$r['countryIso'];   // "RO"
$r['countryName'];  // "Romania"
$r['cost'];         // provider price — depends on the country
```

For international numbers the price is requested from the foreign provider and
depends on the destination country. Use `estimate()` to see it before sending:

```php
$e = $sms->estimate('+40712345678', 'Salut');
// $e['destination'] === 'international', $e['countryName'] === 'Romania'
```

## When international is disabled

**Single send** — throws a [`ValidationException`](errors.md). Because the error
is not tied to a specific field, it is reported under the `_` key:

```php
use Nikba\SmsMdPhpApi\Exception\ValidationException;

try {
    $sms->sendMessage('+40712345678', 'Salut', 'sms.md');
} catch (ValidationException $e) {
    echo $e->firstError('_');
    // "The phone number is not allowed. International numbers are not supported for your account."
}
```

**OTP send** — same, but reported under the `phone` key:

```php
$e->firstError('phone');
```

**Bulk send** — disallowed numbers are skipped individually and returned in
`rejected`; the allowed ones still go out:

```php
$r = $sms->sendBulk(['69123456', '+1202555000'], 'Hi', 'sms.md');
$r['queued'];   // 1  (the Moldovan number)
$r['rejected']; // ['+1202555000']
```

## See also

- [Messages](messages.md)
- [Bulk sending](bulk.md)
- [Error handling](errors.md)
