<?php declare(strict_types=1);

namespace Warexo\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\ListPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Price\Struct\ReferencePrice;
use Shopware\Core\Checkout\Cart\Price\Struct\RegulationPrice;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;

class DecimalQuantityCartPriceNormalizer implements CartProcessorInterface
{
    public function __construct(private readonly DecimalQuantityMapper $quantityMapper)
    {
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        foreach ($toCalculate->getLineItems()->filterFlatByType(LineItem::PRODUCT_LINE_ITEM_TYPE) as $lineItem) {
            if (!$lineItem instanceof LineItem || !DecimalQuantityCartData::has($data, $lineItem)) {
                continue;
            }

            $price = $lineItem->getPrice();
            $definition = $lineItem->getPriceDefinition();
            if (!$price instanceof CalculatedPrice || !$definition instanceof QuantityPriceDefinition) {
                continue;
            }

            $normalizedUnitPrice = $this->quantityMapper->fromCoreUnitPrice($definition->getPrice());
            $lineItem->setPrice(new CalculatedPrice(
                $normalizedUnitPrice,
                $price->getTotalPrice(),
                $price->getCalculatedTaxes(),
                $price->getTaxRules(),
                $price->getQuantity(),
                $this->normalizeReferencePrice($price->getReferencePrice(), $normalizedUnitPrice),
                $this->normalizeListPrice($price, $definition, $normalizedUnitPrice),
                $this->normalizeRegulationPrice($definition)
            ));
        }
    }

    private function normalizeReferencePrice(?ReferencePrice $referencePrice, float $normalizedUnitPrice): ?ReferencePrice
    {
        if ($referencePrice === null || $referencePrice->getPurchaseUnit() <= 0.0 || $referencePrice->getReferenceUnit() <= 0.0) {
            return null;
        }

        return new ReferencePrice(
            round(
                $normalizedUnitPrice / $referencePrice->getPurchaseUnit() * $referencePrice->getReferenceUnit(),
                DecimalQuantityMapper::SCALE + 2
            ),
            $referencePrice->getPurchaseUnit(),
            $referencePrice->getReferenceUnit(),
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
        if ($definition->getRegulationPrice() === null) {
            return null;
        }

        return new RegulationPrice($this->quantityMapper->fromCoreUnitPrice($definition->getRegulationPrice()));
    }
}
