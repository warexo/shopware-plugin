<?php declare(strict_types=1);

namespace Warexo\Subscriber;

use Shopware\Core\System\SalesChannel\Event\SalesChannelProcessCriteriaEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityFeatureDecider;

class StorefrontProductCriteriaSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly DecimalQuantityFeatureDecider $featureDecider
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sales_channel.product.process.criteria' => 'onSalesChannelProductCriteria',
        ];
    }

    public function onSalesChannelProductCriteria(SalesChannelProcessCriteriaEvent $event): void
    {
        if (!$this->featureDecider->isEnabled($event->getSalesChannelContext()->getSalesChannelId())) {
            return;
        }

        $event->getCriteria()->addAssociation('warexoExtension');
    }
}
