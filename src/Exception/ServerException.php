<?php

declare(strict_types=1);

namespace Nikba\SmsMdPhpApi\Exception;

/**
 * Thrown on 5xx: an error on the sms.md side (`INTERNAL_ERROR`).
 */
class ServerException extends ApiException
{
}
