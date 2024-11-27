<?php declare(strict_types=1);

namespace Warexo\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class ProductOptionCartProcessor implements CartProcessorInterface
{
    private QuantityPriceCalculator $calculator;

    public function __construct(QuantityPriceCalculator $calculator) {
        $this->calculator = $calculator;
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $selections = $data->get('optionValueSelections');

        $lineItems = $toCalculate->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        foreach ($lineItems as $lineItem) {
            if (isset($selections[$lineItem->getId()])) {
                $lineItemSelections = $selections[$lineItem->getId()];
                $lineItem->setPayloadValue('warexoProductOptionSelections', $lineItemSelections);

                $surcharge = 0;
                $basePrice = $lineItem->getPrice()->getUnitPrice();

                foreach($lineItemSelections as $selection) {
                    if ($selection['surcharge']) {
                        if ($selection['surcharge']['type'] === '%') {
                            $surcharge += $basePrice * ($selection['surcharge']['price'] / 100);
                        } else {
                            $surcharge += $selection['surcharge']['price'];
                        }
                    }
                }

                if ($surcharge !== 0) {
                    // build new price definition
                    $definition = new QuantityPriceDefinition(
                        $basePrice + $surcharge,
                        $lineItem->getPrice()->getTaxRules(),
                        $lineItem->getPrice()->getQuantity()
                    );

                    // build CalculatedPrice over calculator class for overwritten price
                    $calculated = $this->calculator->calculate($definition, $context);

                    // set new price into line item
                    $lineItem->setPrice($calculated);
                    $lineItem->setPriceDefinition($definition);
                }

            }
        }
    }
}