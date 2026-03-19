<?php

namespace Warexo\Core\Content\Product\Stock;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityFeatureDecider;
use Shopware\Core\Content\Product\Stock\AbstractStockStorage;
use Shopware\Core\Content\Product\Stock\StockDataCollection;
use Shopware\Core\Content\Product\Stock\StockLoadRequest;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class StockStorage extends AbstractStockStorage
{
    public function __construct(
        private readonly AbstractStockStorage $decorated,
        private readonly Connection $connection,
        private readonly DecimalQuantityFeatureDecider $featureDecider
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

    public function alter(array $changes, Context $context): void
    {
        $this->decorated->alter($changes, $context);
    }

    public function index(array $productIds, Context $context): void
    {
        $this->decorated->index($productIds, $context);
    }
}