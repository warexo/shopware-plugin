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
use Shopware\Core\Checkout\Cart\Price\Struct\RegulationPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityFeatureDecider;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityRequestTransformer;

class ProductOptionCartProcessor implements CartProcessorInterface
{
    public function __construct(
        private readonly QuantityPriceCalculator $calculator,
        private readonly DecimalQuantityFeatureDecider $featureDecider,
        private readonly DecimalQuantityMapper $quantityMapper,
        private readonly DecimalQuantityRequestTransformer $requestTransformer
    ) {
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $selections = $data->get('optionValueSelections');
        if (!is_array($selections)) {
            $selections = [];
        }

        $decimalQuantityEnabled = $this->featureDecider->isEnabled($context->getSalesChannelId());

        $lineItems = $toCalculate->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        foreach ($lineItems as $lineItem) {
            $lineItemSelections = [];
            if (isset($selections[$lineItem->getId()]) && is_array($selections[$lineItem->getId()])) {
                $lineItemSelections = $selections[$lineItem->getId()];
                $lineItem->setPayloadValue('warexoProductOptionSelections', $lineItemSelections);
            }

            $isDecimalQuantity = $decimalQuantityEnabled
                && $this->requestTransformer->isTruthy($lineItem->getPayloadValue('warexoIsDecimalQuantity'));

            if (!$isDecimalQuantity && $lineItemSelections === []) {
                continue;
            }

            if ($isDecimalQuantity) {
                $this->synchronizeDecimalQuantityInformation($lineItem);
            }

            $price = $lineItem->getPrice();
            if ($price === null) {
                continue;
            }

            $businessUnitPrice = $this->resolveBusinessUnitPrice($data, $lineItem, $price->getUnitPrice());
            $normalizedUnitPrice = $businessUnitPrice;
            $basePrice = $isDecimalQuantity
                ? $this->quantityMapper->toCoreUnitPrice($businessUnitPrice)
                : $businessUnitPrice;

            $surcharge = 0.0;
            foreach ($lineItemSelections as $selection) {
                $selectionSurcharge = $selection['surcharge'] ?? null;
                if (!is_array($selectionSurcharge) || !isset($selectionSurcharge['price'], $selectionSurcharge['type'])) {
                    continue;
                }

                $surchargePrice = (float) $selectionSurcharge['price'];
                if ($selectionSurcharge['type'] === '%') {
                    $surcharge += $basePrice * ($surchargePrice / 100);
                    $normalizedUnitPrice += $businessUnitPrice * ($surchargePrice / 100);

                    continue;
                }

                $surcharge += $isDecimalQuantity
                    ? $this->quantityMapper->toCoreUnitPrice($surchargePrice)
                    : $surchargePrice;
                $normalizedUnitPrice += $surchargePrice;
            }

            if (!$isDecimalQuantity && $surcharge === 0.0) {
                continue;
            }

            $definition = new QuantityPriceDefinition(
                $basePrice + $surcharge,
                $price->getTaxRules(),
                $price->getQuantity()
            );

            $existingDefinition = $lineItem->getPriceDefinition();
            if ($existingDefinition instanceof QuantityPriceDefinition) {
                if ($existingDefinition->getReferencePriceDefinition() !== null) {
                    $definition->setReferencePriceDefinition($existingDefinition->getReferencePriceDefinition());
                }

                $listPrice = $existingDefinition->getListPrice();
                if ($listPrice !== null) {
                    $definition->setListPrice(
                        $isDecimalQuantity ? $this->quantityMapper->toCoreUnitPrice($listPrice) : $listPrice
                    );
                }

                $regulationPrice = $existingDefinition->getRegulationPrice();
                if ($regulationPrice !== null) {
                    $definition->setRegulationPrice(
                        $isDecimalQuantity ? $this->quantityMapper->toCoreUnitPrice($regulationPrice) : $regulationPrice
                    );
                }
            }

            $calculated = $this->calculator->calculate($definition, $context);
            if ($isDecimalQuantity) {
                $calculated = $this->normalizeCalculatedPrice($lineItem, $calculated, $normalizedUnitPrice);
            }

            $lineItem->setPrice($calculated);
            $lineItem->setPriceDefinition($definition);
        }
    }

    private function resolveBusinessUnitPrice(CartDataCollection $data, LineItem $lineItem, float $fallback): float
    {
        $businessUnitPrices = $data->get('warexoBusinessUnitPrices');
        if (is_array($businessUnitPrices) && isset($businessUnitPrices[$lineItem->getId()]) && is_numeric($businessUnitPrices[$lineItem->getId()])) {
            return round((float) $businessUnitPrices[$lineItem->getId()], 2);
        }

        $referencedId = $lineItem->getReferencedId();
        if ($referencedId === null) {
            return $fallback;
        }

        $product = $data->get('product-' . $referencedId);
        if (!$product instanceof SalesChannelProductEntity) {
            return $fallback;
        }

        $calculatedPrice = $product->getCalculatedPrice();
        if ($calculatedPrice === null) {
            return $fallback;
        }

        return $calculatedPrice->getUnitPrice();
    }

    private function normalizeCalculatedPrice(LineItem $lineItem, CalculatedPrice $price, float $normalizedUnitPrice): CalculatedPrice
    {
        $decimalQuantity = $lineItem->getPayloadValue('warexoDecimalQuantity');
        if (!is_float($decimalQuantity) && !is_int($decimalQuantity)) {
            $decimalQuantity = $this->quantityMapper->fromCoreQuantity($lineItem->getQuantity());
        }

        $normalizedTotalPrice = round($normalizedUnitPrice * (float) $decimalQuantity, DecimalQuantityMapper::SCALE + 2);
        $taxFactor = $price->getTotalPrice() !== 0.0 ? $normalizedTotalPrice / $price->getTotalPrice() : 1.0;

        return new CalculatedPrice(
            $normalizedUnitPrice,
            $normalizedTotalPrice,
            $this->cloneCalculatedTaxes($price->getCalculatedTaxes(), $taxFactor),
            $price->getTaxRules(),
            $price->getQuantity(),
            $this->normalizeReferencePrice($price->getReferencePrice()),
            $this->normalizeListPrice($price),
            $this->normalizeRegulationPrice($price->getRegulationPrice())
        );
    }

    private function normalizeReferencePrice(?ReferencePrice $referencePrice): ?ReferencePrice
    {
        if ($referencePrice === null) {
            return null;
        }

        return new ReferencePrice(
            $this->quantityMapper->fromCoreUnitPrice($referencePrice->getPrice()),
            $referencePrice->getPurchaseUnit(),
            $referencePrice->getReferenceUnit(),
            $referencePrice->getUnitName()
        );
    }

    private function normalizeListPrice(CalculatedPrice $price): ?ListPrice
    {
        $listPrice = $price->getListPrice();
        if ($listPrice === null) {
            return null;
        }

        return ListPrice::createFromUnitPrice(
            $this->quantityMapper->fromCoreUnitPrice($price->getUnitPrice()),
            $this->quantityMapper->fromCoreUnitPrice($listPrice->getPrice())
        );
    }

    private function normalizeRegulationPrice(?RegulationPrice $regulationPrice): ?RegulationPrice
    {
        if ($regulationPrice === null) {
            return null;
        }

        return new RegulationPrice(
            $this->quantityMapper->fromCoreUnitPrice($regulationPrice->getPrice())
        );
    }

    private function cloneCalculatedTaxes(CalculatedTaxCollection $calculatedTaxes, float $factor): CalculatedTaxCollection
    {
        $cloned = new CalculatedTaxCollection();

        foreach ($calculatedTaxes as $calculatedTax) {
            $cloned->add(new CalculatedTax(
                $calculatedTax->getTax() * $factor,
                $calculatedTax->getTaxRate(),
                $calculatedTax->getPrice() * $factor,
                $calculatedTax->getLabel()
            ));
        }

        return $cloned;
    }

    private function synchronizeDecimalQuantityInformation(LineItem $lineItem): void
    {
        $existing = $lineItem->getQuantityInformation();
        $quantityInformation = new QuantityInformation();

        $minPurchase = $lineItem->getPayloadValue('warexoDecimalMinPurchase');
        if (!is_float($minPurchase) && !is_int($minPurchase)) {
            $minPurchase = $existing?->getMinPurchase();
        } else {
            $minPurchase = $this->quantityMapper->toCoreQuantity((float) $minPurchase);
        }

        $purchaseSteps = $lineItem->getPayloadValue('warexoDecimalPurchaseSteps');
        if (!is_float($purchaseSteps) && !is_int($purchaseSteps)) {
            $purchaseSteps = $existing?->getPurchaseSteps();
        } else {
            $purchaseSteps = $this->quantityMapper->toCoreQuantity((float) $purchaseSteps);
        }

        $maxPurchase = $lineItem->getPayloadValue('warexoDecimalMaxPurchase');
        if (!is_float($maxPurchase) && !is_int($maxPurchase)) {
            $maxPurchase = $existing?->getMaxPurchase();
        } else {
            $maxPurchase = $this->quantityMapper->toCoreQuantity((float) $maxPurchase);
        }

        $quantityInformation->setMinPurchase(max(1, (int) ($minPurchase ?? 1)));
        $quantityInformation->setPurchaseSteps(max(1, (int) ($purchaseSteps ?? 1)));

        if ($maxPurchase !== null) {
            $quantityInformation->setMaxPurchase(max(1, (int) $maxPurchase));
        }

        $lineItem->setQuantityInformation($quantityInformation);
    }
}