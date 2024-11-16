<?php

namespace App\Utils;

class PriceUtils
{
    public static function format(float $amount, string $format = '$%1'): string
    {
        $amountFixed = number_format($amount, 2, '.', ',');

        return str_replace('%1', $amountFixed, $format);
    }
}
