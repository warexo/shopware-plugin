<?php

namespace Warexo\Core\Content\Sitemap\Provider;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Sitemap\Provider\AbstractUrlProvider;
use Shopware\Core\Content\Sitemap\Service\ConfigHandler;
use Shopware\Core\Content\Sitemap\Struct\UrlResult;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\FetchModeHelper;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Content\Sitemap\Struct\Url;

use Symfony\Component\Routing\RouterInterface;

class ProductUrlProvider extends AbstractUrlProvider
{
    final public const CHANGE_FREQ = 'hourly';

    private const CONFIG_HIDE_AFTER_CLOSEOUT = 'core.listing.hideCloseoutProductsWhenOutOfStock';

    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractUrlProvider $decorated,
        private readonly Connection $connection
    ) {
    }

    public function getDecorated(): AbstractUrlProvider
    {
        return $this->decorated;
    }

    public function getName(): string
    {
        return $this->decorated->getName();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function getUrls(SalesChannelContext $context, int $limit, ?int $offset = null): UrlResult
    {
        $result = $this->decorated->getUrls($context, $limit, $offset);

        if (empty($result->getUrls())) {
            return $result;
        }

        $productIds = array_map(static function (Url $url) {
            return $url->getIdentifier();
        }, $result->getUrls());

        $query = $this->connection->createQueryBuilder();
        $query->select([
            'product.id as id',
            /*'main_category.category_id',
            'main_category.sales_channel_id',
            'category_translation.name',
            'category_translation.custom_fields',
            'JSON_UNQUOTE(JSON_EXTRACT(category_translation.custom_fields, "$.custom_warexo_canonical_saleschannel")) as custom_warexo_canonical_saleschannel'*/
        ]);
        $query->from('main_category');
        $query->leftJoin('main_category', 'product', 'product', 'product.id = main_category.product_id OR product.parent_id = main_category.product_id');
        $query->leftJoin('main_category', 'category_translation', 'category_translation', 'category_translation.category_id = main_category.category_id');
        $query->andWhere('product.id IN (:ids)');
        $query->andWhere('main_category.sales_channel_id = :sales_channel');
        $query->andWhere('JSON_CONTAINS_PATH(category_translation.custom_fields, "one", "$.custom_warexo_canonical_saleschannel")');
        $query->andWhere('JSON_UNQUOTE(JSON_EXTRACT(category_translation.custom_fields, "$.custom_warexo_canonical_saleschannel")) != :raw_sales_channel');
        $query->andWhere('JSON_UNQUOTE(JSON_EXTRACT(category_translation.custom_fields, "$.custom_warexo_canonical_mode")) != "category"');
        $query->setParameter('ids', Uuid::fromHexToBytesList($productIds), ArrayParameterType::STRING);
        $query->setParameter('sales_channel', Uuid::fromHexToBytes($context->getSalesChannelId()));
        $query->setParameter('raw_sales_channel', $context->getSalesChannelId());

        $toExclude = Uuid::fromBytesToHexList($query->executeQuery()->fetchFirstColumn());

        $urls = array_filter($result->getUrls(), static function (Url $url) use ($toExclude) {
            return !in_array($url->getIdentifier(), $toExclude);
        });

        return new UrlResult($urls, $result->getNextOffset());
    }
}