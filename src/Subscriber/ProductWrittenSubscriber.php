<?php declare(strict_types=1);

namespace Warexo\Subscriber;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityFeatureDecider;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityValidator;
use Warexo\Extension\Content\Product\ProductExtensionDefinition;
use Shopware\Core\Content\Product\Events\ProductNoLongerAvailableEvent;
use Shopware\Core\Content\Product\ProductEvents;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\Uuid\Uuid;

class ProductWrittenSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Connection $connection,
        private readonly EventDispatcherInterface $dispatcher,
        private readonly DecimalQuantityFeatureDecider $featureDecider,
        private readonly DecimalQuantityMapper $quantityMapper,
        private readonly DecimalQuantityValidator $quantityValidator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductEvents::PRODUCT_WRITTEN_EVENT => ['productWritten', -100],
            ProductExtensionDefinition::ENTITY_NAME . '.written' => ['productExtensionWritten', -100],
        ];
    }

    public function productWritten(EntityWrittenEvent $event): void
    {
        if ($this->featureDecider->isEnabled()) {
            $ids = [];

            foreach ($event->getWriteResults() as $result) {
                if ($result->getOperation() === EntityWriteResult::OPERATION_INSERT) {
                    continue;
                }

                $primaryKey = $result->getPrimaryKey();
                if (is_string($primaryKey)) {
                    $ids[] = $primaryKey;
                }
            }

            $this->syncDecimalExtensionValues($ids, $event->getContext());

            return;
        }

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

    public function productExtensionWritten(EntityWrittenEvent $event): void
    {
        if (!$this->featureDecider->isEnabled()) {
            return;
        }

        $extensionIds = [];

        foreach ($event->getWriteResults() as $result) {
            $primaryKey = $result->getPrimaryKey();
            if (is_string($primaryKey)) {
                $extensionIds[] = $primaryKey;
            }
        }

        $extensionIds = array_values(array_unique(array_filter($extensionIds)));
        if ($extensionIds === []) {
            return;
        }

        $productIds = $this->connection->fetchFirstColumn(
            'SELECT LOWER(HEX(product_id)) FROM warexo_product_extension WHERE id IN (:ids)',
            ['ids' => Uuid::fromHexToBytesList($extensionIds)],
            ['ids' => ArrayParameterType::BINARY]
        );

        $this->syncDecimalExtensionValues($productIds, $event->getContext());
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

    private function syncDecimalExtensionValues(array $productIds, Context $context): void
    {
        $productIds = array_values(array_unique(array_filter($productIds)));

        if ($productIds === []) {
            return;
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT LOWER(HEX(product_id)) AS product_id, stock, min_purchase, max_purchase, purchase_steps
             FROM warexo_product_extension
             WHERE product_id IN (:ids)',
            ['ids' => Uuid::fromHexToBytesList($productIds)],
            ['ids' => ArrayParameterType::BINARY]
        );

        if ($rows === []) {
            return;
        }

        foreach ($rows as $row) {
            $stock = $row['stock'] !== null ? (float) $row['stock'] : null;
            $minPurchase = $row['min_purchase'] !== null ? (float) $row['min_purchase'] : null;
            $maxPurchase = $row['max_purchase'] !== null ? (float) $row['max_purchase'] : null;
            $purchaseSteps = $row['purchase_steps'] !== null ? (float) $row['purchase_steps'] : null;

            if (
                !$this->quantityValidator->isValidNullable($stock)
                || !$this->quantityValidator->isValidNullable($minPurchase)
                || !$this->quantityValidator->isValidNullable($maxPurchase)
                || !$this->quantityValidator->isValidNullable($purchaseSteps)
            ) {
                continue;
            }

            $params = [
                'id' => Uuid::fromHexToBytes($row['product_id']),
                'version' => Uuid::fromHexToBytes($context->getVersionId()),
                'stock' => $stock !== null ? $this->quantityMapper->toCoreQuantity($stock) : null,
                'availableStock' => $stock !== null ? $this->quantityMapper->toCoreQuantity($stock) : null,
                'minPurchase' => $minPurchase !== null ? $this->quantityMapper->toCoreQuantity($minPurchase) : null,
                'maxPurchase' => $maxPurchase !== null ? $this->quantityMapper->toCoreQuantity($maxPurchase) : null,
                'purchaseSteps' => $purchaseSteps !== null ? $this->quantityMapper->toCoreQuantity($purchaseSteps) : null,
            ];

            $this->connection->executeStatement(
                'UPDATE product
                 SET stock = COALESCE(:stock, stock),
                     available_stock = COALESCE(:availableStock, available_stock),
                     min_purchase = COALESCE(:minPurchase, min_purchase),
                     max_purchase = :maxPurchase,
                     purchase_steps = COALESCE(:purchaseSteps, purchase_steps)
                 WHERE id = :id AND version_id = :version',
                $params
            );
        }

        $this->updateAvailableFlag($productIds, $context);
    }
}