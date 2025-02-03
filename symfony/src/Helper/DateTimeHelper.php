<?php

namespace App\Helper;

class DateTimeHelper
{
    public static function formatWithTimezone(\DateTimeInterface $dateTime): string
    {
        return $dateTime->format($dateTime->getOffset() === 0 ? 'Y-m-d\TH:i:s\Z' : 'Y-m-d\TH:i:sP');
    }
}