<?php declare(strict_types=1);

namespace Warexo\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ProductOptionCartProcessor implements CartProcessorInterface
{
    public function __construct(private readonly QuantityPriceCalculator $calculator)
    {
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $selections = $data->get('optionValueSelections');
        if (!is_array($selections)) {
            $selections = [];
        }

        $lineItems = $toCalculate->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        foreach ($lineItems as $lineItem) {
            $lineItemSelections = [];
            if (isset($selections[$lineItem->getId()]) && is_array($selections[$lineItem->getId()])) {
                $lineItemSelections = $selections[$lineItem->getId()];
                $lineItem->setPayloadValue('warexoProductOptionSelections', $lineItemSelections);
            }

            if ($lineItemSelections === []) {
                continue;
            }

            if (DecimalQuantityCartData::has($data, $lineItem)) {
                continue;
            }

            $price = $lineItem->getPrice();
            if ($price === null) {
                continue;
            }

            $businessUnitPrice = $this->resolveBusinessUnitPrice($data, $lineItem, $price->getUnitPrice());
            $businessUnitPrice += $this->resolveCustomFormUnitSurcharge($data, $lineItem);

            $surcharge = 0.0;
            foreach ($lineItemSelections as $selection) {
                $selectionSurcharge = $selection['surcharge'] ?? null;
                if (!is_array($selectionSurcharge) || !isset($selectionSurcharge['price'], $selectionSurcharge['type'])) {
                    continue;
                }

                $surchargePrice = (float) $selectionSurcharge['price'];
                if ($selectionSurcharge['type'] === '%') {
                    $surcharge += $businessUnitPrice * ($surchargePrice / 100);

                    continue;
                }

                $surcharge += $surchargePrice;
            }

            if ($surcharge === 0.0) {
                continue;
            }

            $definition = new QuantityPriceDefinition(
                $businessUnitPrice + $surcharge,
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
                    $definition->setListPrice($listPrice);
                }

                $regulationPrice = $existingDefinition->getRegulationPrice();
                if ($regulationPrice !== null) {
                    $definition->setRegulationPrice($regulationPrice);
                }
            }

            $calculated = $this->calculator->calculate($definition, $context);
            $lineItem->setPrice($calculated);
            $lineItem->setPriceDefinition($definition);
        }
    }

    private function resolveBusinessUnitPrice(CartDataCollection $data, LineItem $lineItem, float $fallback): float
    {
        $businessUnitPrices = $data->get(DecimalQuantityCartData::BUSINESS_UNIT_PRICE_DATA_KEY);
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

        return $this->resolveLineItemPrice($product, $lineItem)->getUnitPrice();
    }

    private function resolveLineItemPrice(SalesChannelProductEntity $product, LineItem $lineItem): CalculatedPrice
    {
        $prices = $product->getCalculatedPrices();
        if ($prices->count() === 0) {
            return $product->getCalculatedPrice();
        }

        $quantity = (float) $lineItem->getQuantity();
        $price = $product->getCalculatedPrice();

        foreach ($prices as $tierPrice) {
            $price = $tierPrice;
            if ($quantity <= $tierPrice->getQuantity()) {
                break;
            }
        }

        return $price;
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
}
