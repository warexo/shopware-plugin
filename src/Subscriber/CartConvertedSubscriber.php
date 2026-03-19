<?php declare(strict_types=1);

namespace Warexo\Subscriber;

use Shopware\Core\Checkout\Cart\Order\CartConvertedEvent;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityFeatureDecider;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityRequestTransformer;

class CartConvertedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly DecimalQuantityFeatureDecider $featureDecider,
        private readonly DecimalQuantityMapper $quantityMapper,
        private readonly DecimalQuantityRequestTransformer $requestTransformer
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CartConvertedEvent::class => 'onCartConverted',
        ];
    }

    public function onCartConverted(CartConvertedEvent $event): void
    {
        if (!$this->featureDecider->isEnabled($event->getSalesChannelContext()->getSalesChannelId())) {
            return;
        }

        $convertedCart = $event->getConvertedCart();
        $lineItems = $convertedCart['lineItems'] ?? null;
        if (!is_array($lineItems)) {
            return;
        }

        foreach ($lineItems as $index => $lineItem) {
            if (!is_array($lineItem)) {
                continue;
            }

            $payload = $lineItem['payload'] ?? null;
            if (!is_array($payload) || !$this->requestTransformer->isTruthy($payload['warexoIsDecimalQuantity'] ?? null)) {
                continue;
            }

            $quantity = $lineItem['quantity'] ?? null;
            if (is_int($quantity)) {
                $payload['warexoDecimalQuantity'] ??= $this->quantityMapper->fromCoreQuantity($quantity);
                $payload['warexoCoreQuantity'] ??= $quantity;
            }

            $lineItem['payload'] = $payload;

            $priceDefinition = $lineItem['priceDefinition'] ?? null;
            if ($priceDefinition instanceof QuantityPriceDefinition) {
                $lineItem['priceDefinition'] = $this->normalizeQuantityPriceDefinition($priceDefinition);
            }

            $lineItems[$index] = $lineItem;
        }

        $convertedCart['lineItems'] = $lineItems;
        $event->setConvertedCart($convertedCart);
    }

    private function normalizeQuantityPriceDefinition(QuantityPriceDefinition $definition): QuantityPriceDefinition
    {
        $normalized = new QuantityPriceDefinition(
            $this->quantityMapper->fromCoreUnitPrice($definition->getPrice()),
            $definition->getTaxRules(),
            $definition->getQuantity()
        );
        $normalized->setIsCalculated($definition->isCalculated());
        $normalized->setReferencePriceDefinition($definition->getReferencePriceDefinition());

        if ($definition->getListPrice() !== null) {
            $normalized->setListPrice(
                $this->quantityMapper->fromCoreUnitPrice($definition->getListPrice())
            );
        }

        if ($definition->getRegulationPrice() !== null) {
            $normalized->setRegulationPrice(
                $this->quantityMapper->fromCoreUnitPrice($definition->getRegulationPrice())
            );
        }

        return $normalized;
    }
}