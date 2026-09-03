<?php

declare(strict_types=1);

namespace Nikba\SmsMdPhpApi\Exception;

/**
 * Thrown when the request never produced a valid API response: a network
 * failure, a timeout, or a body that could not be decoded as JSON.
 *
 * Unlike {@see ApiException}, there is no meaningful HTTP status or error code
 * to branch on.
 */
class TransportException extends SmsMdException
{
}
