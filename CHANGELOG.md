# Changelog

All notable changes to `nikba/sms.md-php-api` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-09-03

Complete rewrite targeting the current **sms.md v3 API**. This is a breaking
release: authentication, method names, return values and error handling have
all changed. See the "Migration from 1.x" section of the README.

### Added
- Full **v3** support: `sendMessage()`, `sendBulk()` (up to 500 recipients),
  `estimate()`, `listMessages()`, `getMessage()`, `getBulkMessages()`,
  `messageStatuses()`.
- **OTP** flow: `sendOtp()` and `verifyOtp()`.
- **Sender names**: `listSenderAliases()`, `getSenderAlias()`.
- **Address books & contacts** CRUD: `listAddressBooks()`, `getAddressBook()`,
  `createAddressBook()`, `updateAddressBook()`, `deleteAddressBook()`,
  `listAddressBookContacts()`, `importContacts()`, `deleteContact()`.
- **Account**: `getBalance()` (returns a string to preserve precision).
- **International sending** support documented and covered by tests — foreign
  numbers pass through the same methods; responses expose `destination`,
  `countryIso`, `countryName` and per-country `cost`; bulk reports `rejected`
  numbers when a country is not allowed for the account.
- Typed **exception hierarchy** mapped from the API's machine-readable `code`:
  `ApiException`, `AuthenticationException`, `ForbiddenException`,
  `NotFoundException`, `InsufficientBalanceException`, `ValidationException`
  (with `getErrors()` / `firstError()`), `RateLimitException`,
  `ServerException`, `TransportException` — all extending `SmsMdException`.
- **Enums**: `MessageStatus`, `Encoding` (with segment-length helpers) and
  `Destination`.
- **PSR-18 / PSR-17** transport with automatic client discovery
  (`php-http/discovery`); any PSR-18 client can be injected.
- Legacy **v1** endpoints kept for backward compatibility via `$sms->legacy()`.
- PHPUnit test suite running against a mock HTTP client.

### Changed
- **Authentication** moved from the `?token=` query parameter to the
  `X-Api-Token` header (v1 style remains only inside `legacy()`).
- Sending is now `POST /v3/messages` with a JSON body instead of `GET /v1/send`.
- The v3 response envelope (`{status, data, meta}`) is unwrapped automatically;
  list methods return `['data' => ..., 'meta' => ...]`.
- Minimum PHP raised to **8.1**; codebase uses `declare(strict_types=1)`,
  constructor property promotion, `readonly` properties and named arguments.
- Guzzle is now a dev/optional dependency instead of a hard requirement.

### Fixed
- Broken `validatePhoneNumber()` logic (a regex that blanked a 10-digit string
  and then checked for length 11) — replaced by server-side validation surfaced
  as `ValidationException`.
- README/method mismatch: the documented `send()` did not match the actual
  `sendSms()`.
- Errors returned inside the payload were previously ignored; failures now throw
  typed exceptions.

### Removed
- Old method names: `sendSms()`, `getMessages()`, `getMessageStatuses()`,
  `getSenderAliases()`, `getContacts()`, `getAddressBooks()`,
  `getAddressBookContacts()`, `getStats()`. See the README migration table for
  their v3 replacements.
- `composer.lock` is no longer committed (standard for a library).

## [1.1] - 2023-10-13

### Added
- Additional v1 helper methods (contacts, address books, sender aliases, stats).

## [1.0.1] - 2022-07-21

### Fixed
- Minor fixes to the initial release.

## [1.0.0] - 2022-07-21

### Added
- Initial release: v1 API wrapper over Guzzle (`sendSms`, `getBalance`,
  `getMessages`, and related read methods).

[2.0.0]: https://github.com/Nikba-Creative-Studio/sms.md-php-api/compare/v1.1...v2.0.0
[1.1]: https://github.com/Nikba-Creative-Studio/sms.md-php-api/compare/v1.0.1...v1.1
[1.0.1]: https://github.com/Nikba-Creative-Studio/sms.md-php-api/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/Nikba-Creative-Studio/sms.md-php-api/releases/tag/v1.0.0
