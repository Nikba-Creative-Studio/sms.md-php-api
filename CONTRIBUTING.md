# Contributing

Thanks for taking the time to contribute! This is a small, focused SDK — the
goal is to stay a faithful, well-tested wrapper around the
[sms.md v3 API](https://docs.sms.md/).

## Getting set up

Requires PHP 8.1+ and Composer.

```bash
git clone https://github.com/Nikba-Creative-Studio/sms.md-php-api.git
cd sms.md-php-api
composer install
```

## Running the tests

```bash
composer test
```

Tests run against a **mock HTTP client** (`php-http/mock-client`) — they make no
real network calls, so no API token is needed. Every new method or behaviour
should come with a test; see [`tests/SmsMdTest.php`](tests/SmsMdTest.php) for the
pattern (queue a fake response, call the SDK, assert on the outgoing request and
the returned data).

## Coding standards

- `declare(strict_types=1);` in every file.
- Target PHP 8.1: constructor property promotion, `readonly`, enums, named args,
  `match`.
- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/).
- Add PHPDoc for public methods, including array shapes and the API scope each
  endpoint needs.
- The HTTP layer stays PSR-18/PSR-17 — do not hard-depend on a specific client.

## Submitting changes

1. Branch off `main`.
2. Keep the change focused; update the [docs](docs/) and
   [CHANGELOG.md](CHANGELOG.md) when behaviour changes.
3. Make sure `composer test` passes on your PHP version (CI runs 8.1–8.4).
4. Open a pull request describing the change and linking any relevant part of
   the [API documentation](https://docs.sms.md/).

## Reporting issues

Open a GitHub issue with the SDK version, PHP version, a minimal reproduction,
and the API error `code` (not the message) when relevant. **Never paste your API
token** or other secrets into an issue.
