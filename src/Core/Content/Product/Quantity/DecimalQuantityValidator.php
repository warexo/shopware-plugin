<?php declare(strict_types=1);

namespace Warexo\Core\Content\Product\Quantity;

class DecimalQuantityValidator
{
    public function isValidNullable(?float $value): bool
    {
        if ($value === null) {
            return true;
        }

        return $this->hasValidScale($value);
    }

    public function hasValidScale(float $value): bool
    {
        return abs($value * DecimalQuantityMapper::FACTOR - round($value * DecimalQuantityMapper::FACTOR)) < 0.000001;
    }
}