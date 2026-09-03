<?php

declare(strict_types=1);

namespace Nikba\SmsMdPhpApi\Exception;

/**
 * Thrown on 401: the token is missing, disabled, or invalid (`AUTHENTICATION_REQUIRED`, `INVALID_API_TOKEN`).
 */
class AuthenticationException extends ApiException
{
}
