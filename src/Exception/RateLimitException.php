<?php

declare(strict_types=1);

namespace Nikba\SmsMdPhpApi\Exception;

/**
 * Thrown on 429: too many requests (`RATE_LIMIT_EXCEEDED`).
 */
class RateLimitException extends ApiException
{
}
