<?php

declare(strict_types=1);

namespace Nikba\SmsMdPhpApi\Exception;

/**
 * Thrown on 403: the token is valid but lacks the scope required by this endpoint (`FORBIDDEN`, `SCOPE_FORBIDDEN`).
 */
class ForbiddenException extends ApiException
{
}
