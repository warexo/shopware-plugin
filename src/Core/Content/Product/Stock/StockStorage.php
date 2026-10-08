<?php declare(strict_types=1);

namespace Warexo\Core\Content\Product\Stock;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\Events\InvalidateProductCache;
use Shopware\Core\Content\Product\Stock\StockAlteration;
use Shopware\Core\Defaults;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityFeatureDecider;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;
use Shopware\Core\Content\Product\Stock\AbstractStockStorage;
use Shopware\Core\Content\Product\Stock\StockDataCollection;
use Shopware\Core\Content\Product\Stock\StockLoadRequest;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class StockStorage extends AbstractStockStorage
{
    public function __construct(
        private readonly AbstractStockStorage $decorated,
        private readonly Connection $connection,
        private readonly DecimalQuantityFeatureDecider $featureDecider,
        private readonly EventDispatcherInterface $dispatcher
    )
    {
    }

    public function getDecorated(): AbstractStockStorage
    {
        return $this->decorated;
    }

    public function load(StockLoadRequest $stockRequest, SalesChannelContext $context): StockDataCollection
    {
        $stockData = $this->decorated->load($stockRequest, $context);
        if (!$this->featureDecider->isEnabled($context->getSalesChannelId())) {
            return $stockData;
        }

        $productsIds = $stockRequest->productIds;
        $bytes = Uuid::fromHexToBytesList($productsIds);

        $stocks = $this->connection->fetchAllAssociativeIndexed(
            'SELECT LOWER(HEX(p.product_id)) as id, p.stock, p.min_purchase, p.max_purchase, p.purchase_steps FROM warexo_product_extension p  WHERE p.product_id IN (:ids) GROUP BY p.product_id',
            ['ids' => $bytes, 'version' => Uuid::fromHexToBytes($context->getVersionId())],
            ['ids' => ArrayParameterType::BINARY]
        );

        foreach($stockData as $productId => $data) {
            if (!isset($stocks[$productId])) {
                continue;
            }

            $data->decimalStock = $stocks[$productId]['stock'];
            $data->decimalMinPurchase = $stocks[$productId]['min_purchase'];
            $data->decimalMaxPurchase = $stocks[$productId]['max_purchase'];
            $data->decimalPurchaseSteps = $stocks[$productId]['purchase_steps'];
        }
        return $stockData;
    }

    /**
     * @param list<StockAlteration> $changes
     */
    public function alter(array $changes, Context $context): void
    {
        if ($changes === [] || $context->getVersionId() !== Defaults::LIVE_VERSION) {
            $this->decorated->alter($changes, $context);

            return;
        }

        $ids = array_values(array_unique(array_column($changes, 'productId')));

        $this->connection->transactional(function () use ($changes, $context, $ids): void {
            $this->decorated->alter($changes, $context);

            if (!$this->featureDecider->isEnabled()) {
                return;
            }

            // Keep the decimal source in step with Shopware's atomic stock change.
            // Otherwise the next product write would restore the pre-order stock.
            $this->connection->executeStatement(
                'UPDATE warexo_product_extension extension
                 INNER JOIN product ON product.id = extension.product_id
                 SET extension.stock = GREATEST(product.stock, 0) / :factor,
                     extension.updated_at = NOW(3)
                 WHERE product.id IN (:ids)
                   AND product.version_id = :version
                   AND extension.stock IS NOT NULL',
                [
                    'ids' => Uuid::fromHexToBytesList($ids),
                    'version' => Uuid::fromHexToBytes(Defaults::LIVE_VERSION),
                    'factor' => DecimalQuantityMapper::FACTOR,
                ],
                ['ids' => ArrayParameterType::BINARY]
            );
        });

        $this->dispatcher->dispatch(new InvalidateProductCache($ids, true));
    }

    public function index(array $productIds, Context $context): void
    {
        $this->decorated->index($productIds, $context);
    }
}
