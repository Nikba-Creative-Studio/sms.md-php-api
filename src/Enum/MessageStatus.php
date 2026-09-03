<?php

declare(strict_types=1);

namespace Nikba\SmsMdPhpApi\Enum;

/**
 * Numeric delivery-status ids as returned by `GET /v3/messages/statuses`
 * and used to filter `GET /v3/messages`.
 */
enum MessageStatus: int
{
    case Pending    = 1;
    case Sent       = 2;
    case Delivered  = 3;
    case Resending  = 4;
    case Queued     = 5;
    case Failed     = 9;
}
