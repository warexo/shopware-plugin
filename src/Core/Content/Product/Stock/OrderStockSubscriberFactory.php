<?php declare(strict_types=1);

namespace Warexo\Core\Content\Product\Stock;

use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\Stock\AbstractStockStorage;
use Shopware\Core\Content\Product\Stock\OrderStockSubscriber;

final class OrderStockSubscriberFactory
{
    public static function create(
        Connection $connection,
        AbstractStockStorage $stockStorage,
        bool $enableStockManagement
    ): OrderStockSubscriber {
        return new OrderStockSubscriber($connection, $stockStorage, !$enableStockManagement);
    }
}
