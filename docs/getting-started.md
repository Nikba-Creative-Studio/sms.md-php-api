# Getting started

[← Back to docs index](README.md)

## Requirements

- PHP **8.1** or newer
- The `json` extension (bundled with PHP)
- Any [PSR-18](https://www.php-fig.org/psr/psr-18/) HTTP client

## Installation

```bash
composer require nikba/sms.md-php-api
```

If you don't already have a PSR-18 client in your project, install Guzzle — it
is discovered automatically:

```bash
composer require guzzlehttp/guzzle
```

## Get your credentials

1. Create an API token: **Settings → API** (<https://app.sms.md/settings/api>).
   Pick the [scopes](errors.md#scopes) the token needs.
2. Register a sender name and wait for approval: **Settings → Sender names**.

## Send your first message

```php
require 'vendor/autoload.php';

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

## Handle errors

Wrap calls in a `try/catch`. Branch on the machine-readable error **code**,
never on the message:

```php
use Nikba\SmsMdPhpApi\Exception\ApiException;

try {
    $sms->sendMessage('69123456', 'Hello', 'sms.md');
} catch (ApiException $e) {
    error_log($e->getErrorCode() . ' (' . $e->getHttpCode() . ')');
}
```

See [Error handling](errors.md) for the full exception list.

## Next steps

- [Configuration](configuration.md) — inject a custom HTTP client, set timeouts
- [Messages](messages.md) — scheduling, listing, statuses
- [International sending](international.md)
