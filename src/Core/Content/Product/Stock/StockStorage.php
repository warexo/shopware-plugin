<?php

namespace Warexo\Core\Content\Product\Stock;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\Stock\AbstractStockStorage;
use Shopware\Core\Content\Product\Stock\StockData;
use Shopware\Core\Content\Product\Stock\StockDataCollection;
use Shopware\Core\Content\Product\Stock\StockLoadRequest;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class StockStorage extends AbstractStockStorage
{
    public function __construct(
        private readonly AbstractStockStorage $decorated,
        private readonly Connection $connection,
        private readonly SystemConfigService $systemConfigService
    )
    {
    }

    public function getDecorated(): AbstractStockStorage
    {
        return $this->decorated;
    }

    public function load(StockLoadRequest $stockRequest, SalesChannelContext $context): StockDataCollection
    {
        if (!$this->systemConfigService->get('AggroWarexoPlugin.config.decimal_stock', $context->getSalesChannelId())) {
            return $this->decorated->load($stockRequest, $context);
        }

        $productsIds = $stockRequest->productIds;
        $bytes = Uuid::fromHexToBytesList($productsIds);

        $stocks = $this->connection->fetchAllAssociativeIndexed(
            'SELECT LOWER(HEX(p.product_id)) as id, p.stock, p.min_purchase, p.max_purchase, p.purchase_steps FROM warexo_product_extension p  WHERE p.product_id IN (:ids) GROUP BY p.product_id',
            ['ids' => $bytes, 'version' => Uuid::fromHexToBytes($context->getVersionId())],
            ['ids' => ArrayParameterType::BINARY]
        );
        

        return new StockDataCollection(
            array_map(function (string $productId, array $stock) {
                $stockData = new StockData(
                    $productId,
                    $stock['stock'],
                    $stock['stock'] > 0,
                    $stock['min_purchase'],
                    $stock['max_purchase']
                );
                return $stockData;
            }, array_keys($stocks), $stocks)
        );
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