<?php declare(strict_types=1);

namespace Warexo\Subscriber;

use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Shopware\Core\Checkout\Cart\Event\BeforeLineItemQuantityChangedEvent;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityRequestTransformer;

class BeforeLineItemAddedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly DecimalQuantityMapper $quantityMapper,
        private readonly DecimalQuantityRequestTransformer $requestTransformer
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeLineItemAddedEvent::class => 'onBeforeLineItemAdded',
            BeforeLineItemQuantityChangedEvent::class => 'onBeforeLineItemQuantityChanged',
        ];
    }

    public function onBeforeLineItemAdded(BeforeLineItemAddedEvent $event): void
    {
        $lineItem = $event->getLineItem();
        $originalLineItemId = $lineItem->getId();
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        $decimalPayloads = DecimalQuantityRequestSubscriber::getDecimalPayloads($request);

        $lineItems = $request->request->all('lineItems');

        if (is_array($lineItems)) {
            foreach ($lineItems as $key => $item) {
                if (!is_array($item) || $lineItem->getId() !== $key || !isset($item['warexoProductOptions'])) {
                    continue;
                }

                $cart = $event->getCart();
                $cart->remove($lineItem->getId());
                $lineItem->setId(md5($lineItem->getId() . implode('|', array_values($item['warexoProductOptions']))));
                $lineItem->setPayloadValue('warexoProductOptions', $item['warexoProductOptions']);
                $cart->add($lineItem);
            }
        }

        $cartLineItem = $event->getCart()->getLineItems()->get($lineItem->getId());
        if (!$cartLineItem instanceof LineItem) {
            $cartLineItem = $lineItem;
        }

        $decimalPayload = $decimalPayloads[$originalLineItemId] ?? null;
        $isDecimalQuantity = is_array($decimalPayload)
            && $this->requestTransformer->isTruthy($decimalPayload['warexoIsDecimalQuantity'] ?? null);

        if (!$isDecimalQuantity) {
            return;
        }

        $cartLineItem->setPayloadValue('warexoIsDecimalQuantity', true);

        foreach (['warexoDecimalMinPurchase', 'warexoDecimalMaxPurchase', 'warexoDecimalPurchaseSteps'] as $payloadKey) {
            $payloadValue = $decimalPayload[$payloadKey] ?? null;
            if ($payloadValue !== null) {
                $cartLineItem->setPayloadValue($payloadKey, $payloadValue);
            }
        }

        $cartLineItem->setPayloadValue(
            'warexoDecimalQuantity',
            $this->quantityMapper->fromCoreQuantity($cartLineItem->getQuantity())
        );
    }

    public function onBeforeLineItemQuantityChanged(BeforeLineItemQuantityChangedEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null) {
            return;
        }

        $decimalQuantity = $request->attributes->get('warexoDecimalQuantity');
        if (!is_float($decimalQuantity) && !is_int($decimalQuantity)) {
            return;
        }

        $lineItem = $event->getLineItem();
        $lineItem->setPayloadValue('warexoIsDecimalQuantity', true);
        $lineItem->setPayloadValue('warexoDecimalQuantity', round((float) $decimalQuantity, DecimalQuantityMapper::SCALE));
    }
}