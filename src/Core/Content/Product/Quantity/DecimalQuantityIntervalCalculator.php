<?php declare(strict_types=1);

namespace Warexo\Core\Content\Product\Quantity;

class DecimalQuantityIntervalCalculator
{
    public function __construct(
        private readonly DecimalQuantityMapper $quantityMapper
    ) {
    }

    public function snap(float $quantity, mixed $minPurchase, mixed $purchaseSteps, mixed $maxPurchase): float
    {
        $minPurchase = $this->normalizePositiveNumber($minPurchase) ?? 1.0;
        $purchaseSteps = $this->normalizePositiveNumber($purchaseSteps) ?? 1.0;
        $maxPurchase = $this->normalizePositiveNumber($maxPurchase);

        $coreQuantity = $this->quantityMapper->toCoreQuantity($quantity);
        $coreMinPurchase = $this->quantityMapper->toCoreQuantity($minPurchase);
        $corePurchaseSteps = max(1, $this->quantityMapper->toCoreQuantity($purchaseSteps));

        $steps = (int) round(($coreQuantity - $coreMinPurchase) / $corePurchaseSteps);
        $coreQuantity = max($coreMinPurchase, $coreMinPurchase + ($steps * $corePurchaseSteps));

        if ($maxPurchase !== null) {
            $coreMaxPurchase = max($coreMinPurchase, $this->quantityMapper->toCoreQuantity($maxPurchase));
            if ($coreQuantity > $coreMaxPurchase) {
                $coreQuantity = $coreMinPurchase
                    + (intdiv($coreMaxPurchase - $coreMinPurchase, $corePurchaseSteps) * $corePurchaseSteps);
            }
        }

        return $this->quantityMapper->fromCoreQuantity($coreQuantity);
    }

    private function normalizePositiveNumber(mixed $value): ?float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
            if ($value === '' || !is_numeric($value)) {
                return null;
            }
        }

        if (!is_int($value) && !is_float($value) && !is_string($value)) {
            return null;
        }

        $value = (float) $value;

        return $value > 0.0 ? $value : null;
    }
}
