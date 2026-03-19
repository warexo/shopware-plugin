<?php declare(strict_types=1);

namespace Warexo\Core\Content\Product\Quantity;

class DecimalQuantityMapper
{
    public const SCALE = 3;

    public const FACTOR = 1000;

    public function toCoreQuantity(float $decimalQuantity): int
    {
        return (int) round($decimalQuantity * self::FACTOR);
    }

    public function fromCoreQuantity(int $coreQuantity): float
    {
        return round($coreQuantity / self::FACTOR, self::SCALE);
    }

    public function toCoreUnitPrice(float $decimalUnitPrice): float
    {
        return round($decimalUnitPrice / self::FACTOR, 10);
    }

    public function fromCoreUnitPrice(float $coreUnitPrice): float
    {
        return round($coreUnitPrice * self::FACTOR, self::SCALE);
    }
}