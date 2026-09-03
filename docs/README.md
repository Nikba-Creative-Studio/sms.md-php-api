# sms.md PHP SDK — Documentation

Documentation for [`nikba/sms.md-php-api`](https://packagist.org/packages/nikba/sms.md-php-api), a modern PHP SDK for the [sms.md](https://sms.md/) v3 Client API.

## Contents

- [Getting started](getting-started.md) — install, first message, requirements
- [Configuration](configuration.md) — tokens, HTTP client injection, timeouts
- **Features**
  - [Messages](messages.md) — send, schedule, list, fetch, statuses
  - [Bulk sending](bulk.md) — up to 500 recipients in one call
  - [OTP](otp.md) — send & verify one-time codes
  - [International sending](international.md) — foreign numbers, pricing, restrictions
  - [Sender names](sender-names.md)
  - [Address books & contacts](address-books.md)
  - [Account balance](account.md)
- [Error handling](errors.md) — exceptions, error codes, scopes
- [Legacy v1 API](legacy.md) — deprecated endpoints via `->legacy()`
- [Migration from 1.x](migration.md)

## Reference

- Official API docs: <https://docs.sms.md/>
- Manage your token & sender names: <https://app.sms.md/settings/api>

## At a glance

```php
use Nikba\SmsMdPhpApi\SmsMd;

$sms = new SmsMd('YOUR_API_TOKEN');

$sms->sendMessage('69123456', 'Your code is 1234', 'sms.md');
echo $sms->getBalance(); // "123.45"
```

Every method returns the decoded `data` payload; any API error is thrown as a
typed [exception](errors.md). Keep your token **server-side** — it grants full
account access and can spend your balance.
