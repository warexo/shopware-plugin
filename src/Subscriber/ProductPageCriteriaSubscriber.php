<?php

namespace Warexo\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\PrefixFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Storefront\Page\Product\ProductPageCriteriaEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductPageCriteriaSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageCriteriaEvent::class => 'onProductPageCriteria'
        ];
    }

    public function onProductPageCriteria(ProductPageCriteriaEvent $event)
    {
        $criteria = $event->getCriteria();
        $criteria->addAssociation('warexoProductOptions');
        $criteria->addAssociation('warexoProductOptions.productOptionValues');
        $criteria->addAssociation('warexoProductOptions.productOptionValues.media');
        $criteria->getAssociation('warexoProductOptions')->addSorting(new FieldSorting('position'));
        $criteria->getAssociation('warexoProductOptions.productOptionValues')->addSorting(new FieldSorting('position'));

        // filter pdf media
        $criteria->getAssociation('media')->addFilter(
            new PrefixFilter('media.mimeType', 'image/')
        );
    }
}