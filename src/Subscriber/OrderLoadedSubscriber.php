<?php

namespace Warexo\Subscriber;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\KernelPluginLoader;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderLoadedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly KernelPluginLoader $pluginLoader,
        private readonly Connection $connection
    ){

    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderEvents::ORDER_LOADED_EVENT => 'onOrderLoaded'
        ];
    }

    public function onOrderLoaded(EntityLoadedEvent $event): void
    {
        $magnaPlugin = $this->pluginLoader->getPluginInstance('Redgecko\Magnalister\RedMagnalisterSW6');
        if (!$magnaPlugin || !$magnaPlugin->isActive()) {
            return;
        }

        $orderIds = $event->getIds();
        $magnaData = $this->connection->fetchAllAssociativeIndexed(
            'SELECT `current_orders_id` as id, `special` as order_id, `platform` FROM `magnalister_orders` WHERE `current_orders_id` IN (:orderIds)',
            ['orderIds' => $orderIds],
            ['orderIds' => ArrayParameterType::STRING]
        );

        /** @var OrderEntity $order */
        foreach ($event->getEntities() as $order) {
            if (isset($magnaData[$order->get('id')])) {
                $customFields = $order->getCustomFields() ?? [];
                if ($magnaData['platform'] === 'amazon') {
                    $customFields['amazonorderid'] = $magnaData[$order->get('id')]['order_id'];
                }else if($magnaData['platform'] === 'ebay') {
                    $customFields['ebayorderid'] = $magnaData[$order->get('id')]['order_id'];
                }
                $order->setCustomFields($customFields);
            }
        }
    }
}