<?php declare(strict_types=1);

namespace Warexo\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ProductPageLoadedSubscriber
 * @package Warexo\Subscriber
 *
 * Unlock default product layout functionality
 */
class ProductPageLoadedSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            \Shopware\Storefront\Page\Product\ProductPageLoadedEvent::class => 'onProductPageLoaded'
        ];
    }

    public function onProductPageLoaded(\Shopware\Storefront\Page\Product\ProductPageLoadedEvent $event)
    {
        $page = $event->getPage();
        $product = $page->getProduct();
        if ($cmsPage = $product->getCmsPage()) {
            $page->setCmsPage($cmsPage);
        }
    }
}