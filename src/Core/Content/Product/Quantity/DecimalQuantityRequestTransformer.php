<?php declare(strict_types=1);

namespace Warexo\Core\Content\Product\Quantity;

class DecimalQuantityRequestTransformer
{
    public function __construct(
        private readonly DecimalQuantityMapper $quantityMapper,
        private readonly DecimalQuantityValidator $quantityValidator
    ) {
    }

    /**
     * @return array{decimalQuantity: float, coreQuantity: int}|null
     */
    public function transform(mixed $rawQuantity): ?array
    {
        $decimalQuantity = $this->normalize($rawQuantity);
        if ($decimalQuantity === null || $decimalQuantity <= 0.0) {
            return null;
        }

        if (!$this->quantityValidator->hasValidScale($decimalQuantity)) {
            return null;
        }

        return [
            'decimalQuantity' => round($decimalQuantity, DecimalQuantityMapper::SCALE),
            'coreQuantity' => $this->quantityMapper->toCoreQuantity($decimalQuantity),
        ];
    }

    public function isDecimalPayload(array $payload): bool
    {
        return $this->isTruthy($payload['warexoIsDecimalQuantity'] ?? null);
    }

    public function isTruthy(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return $value === 1 || $value === 1.0;
        }

        if (!is_string($value)) {
            return false;
        }

        return in_array(mb_strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function normalize(mixed $rawQuantity): ?float
    {
        if (is_int($rawQuantity) || is_float($rawQuantity)) {
            return (float) $rawQuantity;
        }

        if (!is_string($rawQuantity)) {
            return null;
        }

        $normalized = str_replace(',', '.', trim($rawQuantity));
        if ($normalized == '' || !is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }
}