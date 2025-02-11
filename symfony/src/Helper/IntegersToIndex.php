<?php

namespace App\Helper;

class IntegersToIndex
{
    public static function convert(array $integers): string
    {
        sort($integers);

        return implode('_', $integers);
    }
}
