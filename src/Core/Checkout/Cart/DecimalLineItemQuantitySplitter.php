<?php declare(strict_types=1);

namespace Warexo\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemQuantitySplitter;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class DecimalLineItemQuantitySplitter extends LineItemQuantitySplitter
{
    public function __construct(
        private readonly LineItemQuantitySplitter $decorated,
        QuantityPriceCalculator $quantityPriceCalculator
    ) {
        parent::__construct($quantityPriceCalculator);
    }

    public function split(LineItem $item, int $quantity, SalesChannelContext $context): LineItem
    {
        if (!$this->isDecimalLineItem($item)) {
            return $this->decorated->split($item, $quantity, $context);
        }

        $price = $item->getPrice();
        if ($item->getQuantity() === $quantity) {
            return clone $item;
        }

        if (!$price instanceof CalculatedPrice) {
            return $this->decorated->split($item, $quantity, $context);
        }

        $split = clone $item;
        $split->setQuantity($quantity);

        $factor = $quantity / $item->getQuantity();
        $split->setPrice(new CalculatedPrice(
            $price->getUnitPrice(),
            $price->getTotalPrice() * $factor,
            $this->splitTaxes($price->getCalculatedTaxes(), $factor),
            $price->getTaxRules(),
            $quantity,
            $price->getReferencePrice(),
            $price->getListPrice(),
            $price->getRegulationPrice()
        ));

        return $split;
    }

    private function isDecimalLineItem(LineItem $lineItem): bool
    {
        $value = $lineItem->getPayloadValue('warexoIsDecimalQuantity');
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (int) $value === 1;
        }

        if (!is_string($value)) {
            return false;
        }

        return in_array(mb_strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function splitTaxes(CalculatedTaxCollection $taxes, float $factor): CalculatedTaxCollection
    {
        $splitTaxes = new CalculatedTaxCollection();

        foreach ($taxes as $tax) {
            $splitTaxes->add(new CalculatedTax(
                $tax->getTax() * $factor,
                $tax->getTaxRate(),
                $tax->getPrice() * $factor,
                $tax->getLabel()
            ));
        }

        return $splitTaxes;
    }
}
