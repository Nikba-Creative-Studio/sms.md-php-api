# OTP (one-time codes)

[← Back to docs index](README.md)

Scope: `otp:send` (covers both sending and verifying).

The OTP flow uses an **OTP application** you configure in your account
(**Settings → OTP**). The application decides the message text, the code length
and lifetime, and the rate limits — so you never build the code yourself.

## Send a code

```php
$data = $sms->sendOtp(
    phone:  '69123456',
    apiKey: 'YOUR_OTP_APP_KEY', // 48 hex chars, from Settings → OTP
    action: 'login',            // optional; rate limits are counted per action
);

$data['id'];         // OTP request id
$data['dateExpire']; // after this the code stops being accepted
```

- `action` — what the code is for (`login`, `register`, `payment`, …).
  Defaults to `default`. **Verification must use the same value.**
- `referenceId` — optional; your own identifier (order id, session id), stored
  with the request. Has no effect on behaviour.

## Verify a code

Returns `true` when the code is valid. An invalid or expired code raises a
[`ValidationException`](errors.md) (or another `ApiException`):

```php
use Nikba\SmsMdPhpApi\Exception\ApiException;

try {
    $sms->verifyOtp(
        phone:  '69123456',
        code:   '482913',           // what the user entered
        apiKey: 'YOUR_OTP_APP_KEY',
        action: 'login',            // must match the send call
    );
    // code is valid → continue the login/registration
} catch (ApiException $e) {
    // invalid or expired code
}
```

> The `action` passed to `verifyOtp()` must match the one used in `sendOtp()`,
> otherwise verification fails.

## Rate limits

The OTP application enforces its own per-`action` rate limits. Exceeding them
returns `429` → [`RateLimitException`](errors.md).

## See also

- [Error handling](errors.md)
- [International sending](international.md) — OTP to a foreign number needs
  international sending enabled for the account
