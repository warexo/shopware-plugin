<?php declare(strict_types=1);

namespace Warexo\Subscriber;

use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class BeforeLineItemAddedSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeLineItemAddedEvent::class => 'onBeforeLineItemAdded'
        ];
    }

    public function onBeforeLineItemAdded(BeforeLineItemAddedEvent $event)
    {
        $lineItem = $event->getLineItem();
        $lineItems = $this->requestStack->getCurrentRequest()->get('lineItems');

        if($lineItems) {
            foreach ($lineItems as $key => $item) {
                if ($lineItem->getId() == $key && isset($item['warexoProductOptions'])) {
                    $cart = $event->getCart();
                    $cart->remove($lineItem->getId());
                    $lineItem->setId(md5( $lineItem->getId(). implode('|', array_values($item['warexoProductOptions']))));
                    $lineItem->setPayloadValue('warexoProductOptions', $item['warexoProductOptions']);
                    $cart->add($lineItem);
                }
            }
        }
    }

    private function processProductOptions($options)
    {
        $result = [];
        foreach ($options as $option) {
            $result[] = $option['id'];
        }
        return $result;
    }
}