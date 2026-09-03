<?php

declare(strict_types=1);

namespace Nikba\SmsMdPhpApi\Enum;

/**
 * Text encoding a message is sent with, which decides the segment size.
 *
 * `gsm-7` fits 160 characters per single segment (153 when concatenated);
 * `ucs-2` (Cyrillic, diacritics, emoji) fits only 70 (67 when concatenated).
 */
enum Encoding: string
{
    case Gsm7 = 'gsm-7';
    case Ucs2 = 'ucs-2';

    /** Characters per segment for a single, non-concatenated message. */
    public function singleSegmentLength(): int
    {
        return $this === self::Gsm7 ? 160 : 70;
    }

    /** Characters per segment once a message spans multiple segments. */
    public function concatenatedSegmentLength(): int
    {
        return $this === self::Gsm7 ? 153 : 67;
    }
}
