<?php

declare(strict_types=1);

namespace Nikba\SmsMdPhpApi\Exception;

/**
 * Thrown on 402: the account balance is too low to send the message (`INSUFFICIENT_BALANCE`).
 */
class InsufficientBalanceException extends ApiException
{
}
