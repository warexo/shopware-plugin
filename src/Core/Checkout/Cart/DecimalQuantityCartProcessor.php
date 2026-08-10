<?php declare(strict_types=1);

namespace Warexo\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\QuantityInformation;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Price\Struct\ReferencePriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;

class DecimalQuantityCartProcessor implements CartProcessorInterface
{
    public function __construct(
        private readonly QuantityPriceCalculator $calculator,
        private readonly DecimalQuantityMapper $quantityMapper,
        private readonly TaxCalculator $taxCalculator
    ) {
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        foreach ($toCalculate->getLineItems()->filterFlatByType(LineItem::PRODUCT_LINE_ITEM_TYPE) as $lineItem) {
            if (!$lineItem instanceof LineItem || !$lineItem->isStackable() || $lineItem->hasPayloadValue('surcharge')) {
                continue;
            }

            $lineItemData = DecimalQuantityCartData::get($data, $lineItem);
            if ($lineItemData === null) {
                continue;
            }

            $price = $lineItem->getPrice();
            if (!$price instanceof CalculatedPrice) {
                continue;
            }

            $this->synchronizeLineItem($lineItem, $lineItemData);

            $normalizedUnitPrice = $this->resolveNormalizedUnitPrice($data, $lineItem, $lineItemData);
            $definition = new QuantityPriceDefinition(
                $this->quantityMapper->toCoreUnitPrice($normalizedUnitPrice),
                $price->getTaxRules(),
                $lineItem->getQuantity()
            );
            $this->copyPriceDefinitionMetadata($lineItem->getPriceDefinition(), $definition, $lineItemData);

            $calculated = $this->calculator->calculate($definition, $context);
            $lineItem->setPrice($this->correctInternalCalculatedPrice(
                $lineItemData,
                $calculated,
                $normalizedUnitPrice,
                $context
            ));
            $lineItem->setPriceDefinition($definition);
        }
    }

    /**
     * @param array<string, mixed> $lineItemData
     */
    private function synchronizeLineItem(LineItem $lineItem, array $lineItemData): void
    {
        $coreQuantity = $this->getPositiveInt($lineItemData['coreQuantity'] ?? null) ?? $lineItem->getQuantity();
        if ($lineItem->getQuantity() !== $coreQuantity) {
            $lineItem->setQuantity($coreQuantity);
        }

        $lineItem->setPayloadValue('warexoIsDecimalQuantity', true);
        $lineItem->setPayloadValue('warexoDecimalQuantity', $lineItemData['decimalQuantity']);
        $lineItem->setPayloadValue('warexoCoreQuantity', $coreQuantity);
        $this->setNullablePayloadValue($lineItem, 'warexoDecimalMinPurchase', $lineItemData['minPurchase'] ?? null);
        $this->setNullablePayloadValue($lineItem, 'warexoDecimalMaxPurchase', $lineItemData['maxPurchase'] ?? null);
        $this->setNullablePayloadValue($lineItem, 'warexoDecimalPurchaseSteps', $lineItemData['purchaseSteps'] ?? null);
        $this->synchronizeQuantityInformation($lineItem, $lineItemData);
    }

    private function setNullablePayloadValue(LineItem $lineItem, string $key, mixed $value): void
    {
        if ($value === null) {
            return;
        }

        $lineItem->setPayloadValue($key, $value);
    }

    /**
     * @param array<string, mixed> $lineItemData
     */
    private function synchronizeQuantityInformation(LineItem $lineItem, array $lineItemData): void
    {
        $quantityInformation = new QuantityInformation();
        $quantityInformation->setMinPurchase(max(1, $this->quantityMapper->toCoreQuantity((float) ($lineItemData['minPurchase'] ?? 1.0))));
        $quantityInformation->setPurchaseSteps(max(1, $this->quantityMapper->toCoreQuantity((float) ($lineItemData['purchaseSteps'] ?? 1.0))));

        if (isset($lineItemData['maxPurchase']) && is_numeric($lineItemData['maxPurchase'])) {
            $quantityInformation->setMaxPurchase(max(1, $this->quantityMapper->toCoreQuantity((float) $lineItemData['maxPurchase'])));
        }

        $lineItem->setQuantityInformation($quantityInformation);
    }

    /**
     * @param array<string, mixed> $lineItemData
     */
    private function resolveNormalizedUnitPrice(CartDataCollection $data, LineItem $lineItem, array $lineItemData): float
    {
        $unitPrice = $this->getFloat($lineItemData['businessUnitPrice'] ?? null) ?? 0.0;
        $unitPrice += $this->resolveCustomFormUnitSurcharge($data, $lineItem);

        $percentageBase = $unitPrice;
        foreach ($this->resolveProductOptionSelections($data, $lineItem) as $selection) {
            $selectionSurcharge = $selection['surcharge'] ?? null;
            if (!is_array($selectionSurcharge) || !isset($selectionSurcharge['price'], $selectionSurcharge['type'])) {
                continue;
            }

            $surchargePrice = (float) $selectionSurcharge['price'];
            if ($selectionSurcharge['type'] === '%') {
                $unitPrice += $percentageBase * ($surchargePrice / 100);

                continue;
            }

            $unitPrice += $surchargePrice;
        }

        return $unitPrice;
    }

    private function resolveCustomFormUnitSurcharge(CartDataCollection $data, LineItem $lineItem): float
    {
        $customFormId = $lineItem->getPayloadValue('customFormId');
        $customFormFields = $lineItem->getPayloadValue('customFormFields');
        if (!is_string($customFormId) || !is_array($customFormFields)) {
            return 0.0;
        }

        $referencedId = $lineItem->getReferencedId();
        if ($referencedId === null) {
            return 0.0;
        }

        $key = 'custom-form-price-' . $referencedId . '-' . $customFormId . '-' . md5(serialize($customFormFields));
        $surcharge = $data->get($key);

        return is_numeric($surcharge) ? (float) $surcharge : 0.0;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resolveProductOptionSelections(CartDataCollection $data, LineItem $lineItem): array
    {
        $selections = $data->get('optionValueSelections');
        if (!is_array($selections) || !isset($selections[$lineItem->getId()]) || !is_array($selections[$lineItem->getId()])) {
            return [];
        }

        return array_values(array_filter($selections[$lineItem->getId()], 'is_array'));
    }

    /**
     * @param array<string, mixed> $lineItemData
     */
    private function copyPriceDefinitionMetadata(mixed $source, QuantityPriceDefinition $target, array $lineItemData): void
    {
        $referencePriceDefinition = $lineItemData['referencePriceDefinition'] ?? null;
        if (is_array($referencePriceDefinition)) {
            $purchaseUnit = $this->getFloat($referencePriceDefinition['purchaseUnit'] ?? null);
            $referenceUnit = $this->getFloat($referencePriceDefinition['referenceUnit'] ?? null);
            $unitName = $referencePriceDefinition['unitName'] ?? null;
            if ($purchaseUnit !== null && $referenceUnit !== null && is_string($unitName)) {
                $target->setReferencePriceDefinition(new ReferencePriceDefinition($purchaseUnit, $referenceUnit, $unitName));
            }
        } elseif ($source instanceof QuantityPriceDefinition && $source->getReferencePriceDefinition() !== null) {
            $target->setReferencePriceDefinition($source->getReferencePriceDefinition());
        }

        $listPrice = $this->getFloat($lineItemData['listPrice'] ?? null);
        if ($listPrice !== null) {
            $target->setListPrice($this->quantityMapper->toCoreUnitPrice($listPrice));
        }

        $regulationPrice = $this->getFloat($lineItemData['regulationPrice'] ?? null);
        if ($regulationPrice !== null) {
            $target->setRegulationPrice($this->quantityMapper->toCoreUnitPrice($regulationPrice));
        }
    }

    /**
     * Shopware rounds the scaled unit price to currency precision before multiplying it by
     * the core quantity. Keep the internal price self-consistent for quantity splitting and
     * promotion calculation, while retaining the exact decimal total.
     *
     * @param array<string, mixed> $lineItemData
     */
    private function correctInternalCalculatedPrice(
        array $lineItemData,
        CalculatedPrice $price,
        float $normalizedUnitPrice,
        SalesChannelContext $context
    ): CalculatedPrice
    {
        $decimalQuantity = $this->getFloat($lineItemData['decimalQuantity'] ?? null);
        if ($decimalQuantity === null) {
            $coreQuantity = $this->getPositiveInt($lineItemData['coreQuantity'] ?? null) ?? $price->getQuantity();
            $decimalQuantity = $this->quantityMapper->fromCoreQuantity($coreQuantity);
        }

        $internalUnitPrice = $this->quantityMapper->toCoreUnitPrice($normalizedUnitPrice);
        $exactTotalPrice = round($normalizedUnitPrice * $decimalQuantity, DecimalQuantityMapper::SCALE + 2);

        return new CalculatedPrice(
            $internalUnitPrice,
            $exactTotalPrice,
            $this->calculateExactTaxes($exactTotalPrice, $price, $context),
            $price->getTaxRules(),
            $price->getQuantity(),
            $price->getReferencePrice(),
            $price->getListPrice(),
            $price->getRegulationPrice()
        );
    }

    private function calculateExactTaxes(
        float $exactTotalPrice,
        CalculatedPrice $price,
        SalesChannelContext $context
    ): CalculatedTaxCollection
    {
        if ($context->getTaxState() === CartPrice::TAX_STATE_FREE) {
            return new CalculatedTaxCollection();
        }

        if ($context->getTaxState() === CartPrice::TAX_STATE_GROSS) {
            return $this->taxCalculator->calculateGrossTaxes($exactTotalPrice, $price->getTaxRules());
        }

        return $this->taxCalculator->calculateNetTaxes($exactTotalPrice, $price->getTaxRules());
    }

    private function getFloat(mixed $value): ?float
    {
        if (!is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }

    private function getPositiveInt(mixed $value): ?int
    {
        if (!is_numeric($value)) {
            return null;
        }

        $value = (int) $value;

        return $value > 0 ? $value : null;
    }
}
