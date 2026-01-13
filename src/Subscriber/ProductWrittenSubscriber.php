<?php declare(strict_types=1);

namespace Warexo\Subscriber;

use Doctrine\DBAL\ArrayParameterType;
use Shopware\Core\Content\Product\Events\ProductNoLongerAvailableEvent;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Content\Product\Stock\AbstractStockStorage;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\Uuid\Uuid;
use Doctrine\DBAL\Connection;

class ProductWrittenSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly EventDispatcherInterface $dispatcher
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_WRITTEN_EVENT => ['productWritten', -100],
        ];
    }

    public function productWritten(EntityWrittenEvent $event): void
    {
        $ids = [];

        foreach ($event->getWriteResults() as $result) {
            if ($result->getOperation() === EntityWriteResult::OPERATION_INSERT || !$result->hasPayload('stock')) {
                continue;
            }

            $ids[] = $result->getPrimaryKey();
        }

        $ids = array_filter(array_unique($ids));

        if (empty($ids)) {
            return;
        }

        $this->connection->executeStatement(
            'UPDATE product SET available_stock = stock WHERE id IN (:ids)',
            ['ids' => Uuid::fromHexToBytesList($ids)],
            ['ids' =>  ArrayParameterType::STRING]
        );

        $this->updateAvailableFlag($ids, $event->getContext());
    }

    private function updateAvailableFlag(array $ids, Context $context): void
    {
        $ids = array_filter(array_unique($ids));

        if (empty($ids)) {
            return;
        }

        $bytes = Uuid::fromHexToBytesList($ids);

        $sql = '
            UPDATE product
            LEFT JOIN product parent
                ON parent.id = product.parent_id
                AND parent.version_id = product.version_id

            SET product.available = IFNULL((
                COALESCE(product.is_closeout, parent.is_closeout, 0) * product.stock
                >=
                COALESCE(product.is_closeout, parent.is_closeout, 0) * IFNULL(IFNULL(product.min_purchase, parent.min_purchase),0)
            ), 0)
            WHERE product.id IN (:ids)
            AND product.version_id = :version
        ';

        $before = $this->connection->fetchAllKeyValue(
            'SELECT LOWER(HEX(id)), available FROM product WHERE id IN (:ids) AND product.version_id = :version',
            ['ids' => $bytes, 'version' => Uuid::fromHexToBytes($context->getVersionId())],
            ['ids' => ArrayParameterType::BINARY]
        );

        RetryableQuery::retryable($this->connection, function () use ($sql, $context, $bytes): void {
            $this->connection->executeStatement(
                $sql,
                ['ids' => $bytes, 'version' => Uuid::fromHexToBytes($context->getVersionId())],
                ['ids' => ArrayParameterType::BINARY]
            );
        });

        $after = $this->connection->fetchAllKeyValue(
            'SELECT LOWER(HEX(id)), available FROM product WHERE id IN (:ids) AND product.version_id = :version',
            ['ids' => $bytes, 'version' => Uuid::fromHexToBytes($context->getVersionId())],
            ['ids' => ArrayParameterType::BINARY]
        );

        $updated = [];
        foreach ($before as $id => $available) {
            if ($available !== $after[$id]) {
                $updated[] = (string) $id;
            }
        }

        if (!empty($updated)) {
            $this->dispatcher->dispatch(new ProductNoLongerAvailableEvent($updated, $context));
        }
    }
}