# Configuration

[← Back to docs index](README.md)

## Constructor

```php
use Nikba\SmsMdPhpApi\SmsMd;

new SmsMd(
    string $apiToken,
    ?Psr\Http\Client\ClientInterface $httpClient = null,
    ?Psr\Http\Message\RequestFactoryInterface $requestFactory = null,
    ?Psr\Http\Message\StreamFactoryInterface $streamFactory = null,
    string $baseUri = 'https://api.sms.md',
);
```

Only the token is required. When the HTTP client and factories are `null`, they
are located automatically with [`php-http/discovery`](https://docs.php-http.org/en/latest/discovery.html).

## Authentication

The SDK sends the token in the `X-Api-Token` header on every v3 request. The
token grants **full access** to the account and can spend your balance:

- keep it server-side; never ship it in mobile apps or frontend code
- store it outside version control (environment variable, secrets manager)

```php
$sms = new SmsMd(getenv('SMS_MD_TOKEN'));
```

## Injecting a custom HTTP client

Pass your own PSR-18 client and PSR-17 factories to control timeouts, retries,
proxies, logging or TLS options:

```php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Nikba\SmsMdPhpApi\SmsMd;

$guzzle  = new Client([
    'timeout'         => 10,
    'connect_timeout' => 5,
]);
$factory = new HttpFactory();

$sms = new SmsMd(getenv('SMS_MD_TOKEN'), $guzzle, $factory, $factory);
```

Any PSR-18 implementation works — Symfony HttpClient (`Psr18Client`), Guzzle,
Buzz, etc. This is also how you inject a **mock client** in tests.

## Base URI

The default is `https://api.sms.md`. Override it only for testing against a
sandbox or a proxy:

```php
$sms = new SmsMd($token, baseUri: 'https://sandbox.example.test');
```

## Timeouts & retries

The SDK does not impose a timeout of its own — configure it on the HTTP client
(see above). Retries are likewise a client concern; if you add a retry
middleware, only retry idempotent reads and `429`
([`RateLimitException`](errors.md)) responses, not sends.
