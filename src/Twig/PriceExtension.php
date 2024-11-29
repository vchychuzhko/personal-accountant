<?php

namespace App\Twig;

use App\Utils\PriceUtils;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class PriceExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('price', [$this, 'format']),
        ];
    }

    public function format(float $value, string $format = '$%1'): string
    {
        return PriceUtils::format($value, $format);
    }
}
