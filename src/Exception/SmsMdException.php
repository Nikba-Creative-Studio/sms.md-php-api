<?php

declare(strict_types=1);

namespace Nikba\SmsMdPhpApi\Exception;

/**
 * Base class for every exception thrown by the SDK.
 *
 * Catch this to handle any SDK failure in one place; catch a more specific
 * subclass to react to a particular kind of error.
 */
class SmsMdException extends \RuntimeException
{
}
