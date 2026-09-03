<?php

declare(strict_types=1);

namespace Nikba\SmsMdPhpApi\Exception;

/**
 * Thrown on 422: the request failed validation (`VALIDATION_ERROR`).
 *
 * Use {@see getErrors()} to read the per-field messages.
 */
class ValidationException extends ApiException
{
    /** First error message for a given field, or null if the field is valid. */
    public function firstError(string $field): ?string
    {
        return $this->getErrors()[$field][0] ?? null;
    }
}
