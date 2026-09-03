<?php

declare(strict_types=1);

namespace Nikba\SmsMdPhpApi\Enum;

/**
 * Whether a message is billed at the Moldovan or the international rate.
 */
enum Destination: string
{
    case Moldova       = 'moldova';
    case International = 'international';
}
