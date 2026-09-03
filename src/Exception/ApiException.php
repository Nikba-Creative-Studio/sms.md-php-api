<?php

declare(strict_types=1);

namespace Nikba\SmsMdPhpApi\Exception;

/**
 * Thrown when the API returns an error envelope
 * (`{"status":"error", "httpCode":..., "code":..., ...}`).
 *
 * Branch on {@see getErrorCode()} (the machine-readable `code`), never on the
 * message: in production the API replaces the message with a generic string.
 */
class ApiException extends SmsMdException
{
    /**
     * @param string               $errorCode Machine-readable error code, e.g. `INSUFFICIENT_BALANCE`.
     * @param int                  $httpCode  HTTP status code returned by the API.
     * @param array<string,string[]> $errors  Per-field validation errors (empty unless VALIDATION_ERROR).
     */
    public function __construct(
        string $message,
        private readonly string $errorCode,
        private readonly int $httpCode,
        private readonly array $errors = [],
    ) {
        parent::__construct($message, $httpCode);
    }

    /**
     * Build the most specific exception for the given API error payload.
     *
     * @param array<string,string[]> $errors
     */
    public static function fromCode(string $code, string $message, int $httpCode, array $errors = []): self
    {
        return match ($code) {
            'AUTHENTICATION_REQUIRED', 'INVALID_API_TOKEN' => new AuthenticationException($message, $code, $httpCode),
            'FORBIDDEN', 'SCOPE_FORBIDDEN'                 => new ForbiddenException($message, $code, $httpCode),
            'NOT_FOUND'                                    => new NotFoundException($message, $code, $httpCode),
            'INSUFFICIENT_BALANCE'                         => new InsufficientBalanceException($message, $code, $httpCode),
            'VALIDATION_ERROR'                             => new ValidationException($message, $code, $httpCode, $errors),
            'RATE_LIMIT_EXCEEDED'                          => new RateLimitException($message, $code, $httpCode),
            'INTERNAL_ERROR'                               => new ServerException($message, $code, $httpCode),
            default                                        => new self($message, $code, $httpCode, $errors),
        };
    }

    /** Machine-readable error code (e.g. `VALIDATION_ERROR`). Branch on this. */
    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /** HTTP status code returned by the API. */
    public function getHttpCode(): int
    {
        return $this->httpCode;
    }

    /**
     * Per-field validation errors. Only populated for a {@see ValidationException};
     * the key is the request field name (`_` for errors not tied to a field).
     *
     * @return array<string,string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
