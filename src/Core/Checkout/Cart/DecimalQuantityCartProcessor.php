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
use Shopware\Core\Checkout\Cart\Price\Struct\ListPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Price\Struct\ReferencePrice;
use Shopware\Core\Checkout\Cart\Price\Struct\ReferencePriceDefinition;
use Shopware\Core\Checkout\Cart\Price\Struct\RegulationPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;

class DecimalQuantityCartProcessor implements CartProcessorInterface
{
    public function __construct(
        private readonly QuantityPriceCalculator $calculator,
        private readonly DecimalQuantityMapper $quantityMapper
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
            $lineItem->setPrice($this->normalizeCalculatedPrice($lineItemData, $calculated, $definition, $normalizedUnitPrice));
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
     * @param array<string, mixed> $lineItemData
     */
    private function normalizeCalculatedPrice(array $lineItemData, CalculatedPrice $price, QuantityPriceDefinition $definition, float $normalizedUnitPrice): CalculatedPrice
    {
        $decimalQuantity = $this->getFloat($lineItemData['decimalQuantity'] ?? null);
        if ($decimalQuantity === null) {
            $coreQuantity = $this->getPositiveInt($lineItemData['coreQuantity'] ?? null) ?? $price->getQuantity();
            $decimalQuantity = $this->quantityMapper->fromCoreQuantity($coreQuantity);
        }

        $normalizedTotalPrice = round($normalizedUnitPrice * $decimalQuantity, DecimalQuantityMapper::SCALE + 2);
        $taxFactor = $price->getTotalPrice() !== 0.0 ? $normalizedTotalPrice / $price->getTotalPrice() : 1.0;

        return new CalculatedPrice(
            $normalizedUnitPrice,
            $normalizedTotalPrice,
            $this->cloneCalculatedTaxes($price->getCalculatedTaxes(), $taxFactor),
            $price->getTaxRules(),
            $price->getQuantity(),
            $this->normalizeReferencePrice($price->getReferencePrice(), $normalizedUnitPrice),
            $this->normalizeListPrice($price, $definition, $normalizedUnitPrice),
            $this->normalizeRegulationPrice($definition)
        );
    }

    private function normalizeReferencePrice(?ReferencePrice $referencePrice, float $normalizedUnitPrice): ?ReferencePrice
    {
        if ($referencePrice === null) {
            return null;
        }

        $purchaseUnit = $referencePrice->getPurchaseUnit();
        $referenceUnit = $referencePrice->getReferenceUnit();
        if ($purchaseUnit <= 0.0 || $referenceUnit <= 0.0) {
            return null;
        }

        return new ReferencePrice(
            round($normalizedUnitPrice / $purchaseUnit * $referenceUnit, DecimalQuantityMapper::SCALE + 2),
            $purchaseUnit,
            $referenceUnit,
            $referencePrice->getUnitName()
        );
    }

    private function normalizeListPrice(CalculatedPrice $price, QuantityPriceDefinition $definition, float $normalizedUnitPrice): ?ListPrice
    {
        if ($price->getListPrice() === null || $definition->getListPrice() === null) {
            return null;
        }

        return ListPrice::createFromUnitPrice(
            $normalizedUnitPrice,
            $this->quantityMapper->fromCoreUnitPrice($definition->getListPrice())
        );
    }

    private function normalizeRegulationPrice(QuantityPriceDefinition $definition): ?RegulationPrice
    {
        $regulationPrice = $definition->getRegulationPrice();
        if ($regulationPrice === null) {
            return null;
        }

        return new RegulationPrice($this->quantityMapper->fromCoreUnitPrice($regulationPrice));
    }

    private function cloneCalculatedTaxes(CalculatedTaxCollection $calculatedTaxes, float $factor): CalculatedTaxCollection
    {
        $cloned = new CalculatedTaxCollection();

        foreach ($calculatedTaxes as $calculatedTax) {
            $cloned->add(new CalculatedTax(
                $calculatedTax->getTax() * $factor,
                $calculatedTax->getTaxRate(),
                $calculatedTax->getPrice() * $factor
            ));
        }

        return $cloned;
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
