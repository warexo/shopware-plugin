<?php

namespace Warexo\Core\Content\Sitemap\Provider;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
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

/**
 * Modified to fetch warexo custom canonical urls and only for parent products no matter if variants exist,
 * maybe add an option later to allow variant based sitemaps
 */
class ProductUrlProvider extends AbstractUrlProvider
{
    final public const CHANGE_FREQ = 'hourly';

    private const CONFIG_HIDE_AFTER_CLOSEOUT = 'core.listing.hideCloseoutProductsWhenOutOfStock';

    /**
     * @internal
     */
    public function __construct(
        private readonly AbstractUrlProvider $decorated,
        private readonly ConfigHandler $configHandler,
        private readonly Connection $connection,
        private readonly ProductDefinition $definition,
        private readonly IteratorFactory $iteratorFactory,
        private readonly RouterInterface $router,
        private readonly SystemConfigService $systemConfigService
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

    public function getUrls(SalesChannelContext $context, int $limit, ?int $offset = null): UrlResult
    {
        list($products, $unfiltered) = $this->getProducts($context, $limit, $offset);

        if (empty($unfiltered)) {
            return new UrlResult([], null);
        }

        $keys = FetchModeHelper::keyPair($products);

        $seoUrls = $this->getSeoUrls(array_values($keys), 'frontend.detail.page', $context, $this->connection);

        $seoUrls = FetchModeHelper::groupUnique($seoUrls);

        $urls = [];
        $url = new Url();

        foreach ($products as $product) {
            $lastMod = $product['updated_at'] ?: $product['created_at'];

            $lastMod = (new \DateTime($lastMod))->format(Defaults::STORAGE_DATE_TIME_FORMAT);

            $newUrl = clone $url;

            if (isset($seoUrls[$product['id']])) {
                $newUrl->setLoc($seoUrls[$product['id']]['seo_path_info']);
            } else {
                $newUrl->setLoc($this->router->generate('frontend.detail.page', ['productId' => $product['id']], UrlGeneratorInterface::ABSOLUTE_PATH));
            }

            $newUrl->setLastmod(new \DateTime($lastMod));
            $newUrl->setChangefreq(self::CHANGE_FREQ);
            $newUrl->setResource(ProductEntity::class);
            $newUrl->setIdentifier($product['id']);

            $urls[] = $newUrl;
        }

        $keys = FetchModeHelper::keyPair($unfiltered);
        $keys = array_keys($keys);
        /** @var int|null $nextOffset */
        $nextOffset = array_pop($keys);

        return new UrlResult($urls, $nextOffset);
    }

    private function getProducts(SalesChannelContext $context, int $limit, ?int $offset): array
    {
        $lastId = null;
        if ($offset) {
            $lastId = ['offset' => $offset];
        }

        $iterator = $this->iteratorFactory->createIterator($this->definition, $lastId);
        $query = $iterator->getQuery();
        $query->setMaxResults($limit);

        $showAfterCloseout = !$this->systemConfigService->get(self::CONFIG_HIDE_AFTER_CLOSEOUT, $context->getSalesChannelId());

        $query->addSelect(
            '`product`.created_at as created_at',
            '`product`.updated_at as updated_at',
        );

        $query->innerJoin('`product`', 'product_visibility', 'visibilities', 'product.visibilities = visibilities.product_id');
        $query->andWhere('`product`.version_id = :versionId');

        if ($showAfterCloseout) {
            $query->andWhere('(`product`.available = 1 OR `product`.is_closeout)');
        } else {
            $query->andWhere('`product`.available = 1');
        }

        $query->andWhere('`product`.active = 1');
        $query->andWhere('(`product`.parent_id IS NULL)');
        $query->andWhere('visibilities.product_version_id = :versionId');
        $query->andWhere('visibilities.sales_channel_id = :salesChannelId');

        $excludedProductIds = $this->getExcludedProductIds($context);
        if (!empty($excludedProductIds)) {
            $query->andWhere('`product`.id NOT IN (:productIds)');
            $query->setParameter('productIds', Uuid::fromHexToBytesList($excludedProductIds), ArrayParameterType::BINARY);
        }

        $query->setParameter('versionId', Uuid::fromHexToBytes(Defaults::LIVE_VERSION));
        $query->setParameter('salesChannelId', Uuid::fromHexToBytes($context->getSalesChannelId()));

        $products = $query->execute()->fetchAllAssociative();

        $filtered = $this->filterForeignCanonicalProducts($products, $context);

        return [ $filtered, $products ];
    }

    private function getExcludedProductIds(SalesChannelContext $salesChannelContext): array
    {
        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();

        $excludedUrls = $this->configHandler->get(ConfigHandler::EXCLUDED_URLS_KEY);
        if (empty($excludedUrls)) {
            return [];
        }

        $excludedUrls = array_filter($excludedUrls, static function (array $excludedUrl) use ($salesChannelId) {
            if ($excludedUrl['resource'] !== ProductEntity::class) {
                return false;
            }

            if ($excludedUrl['salesChannelId'] !== $salesChannelId) {
                return false;
            }

            return true;
        });

        return array_column($excludedUrls, 'identifier');
    }

    private function filterForeignCanonicalProducts(array $products, SalesChannelContext $salesChannelContext): array
    {
        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();

        $productIds = array_column($products, 'id');

        $query = $this->connection->createQueryBuilder();
        $query->select([
            'product.id as id'
        ]);
        $query->from('main_category');
        $query->leftJoin('main_category', 'product', 'product', 'product.id = main_category.product_id OR product.parent_id = main_category.product_id');
        $query->leftJoin('main_category', 'category_translation', 'category_translation', 'category_translation.category_id = main_category.category_id');
        $query->andWhere('product.id IN (:ids)');
        $query->andWhere('JSON_CONTAINS_PATH(category_translation.custom_fields, "one", "$.custom_warexo_canonical_saleschannel")');
        $query->andWhere('JSON_UNQUOTE(JSON_EXTRACT(category_translation.custom_fields, "$.custom_warexo_canonical_saleschannel")) != :raw_sales_channel');
        $query->andWhere('JSON_UNQUOTE(JSON_EXTRACT(category_translation.custom_fields, "$.custom_warexo_canonical_mode")) != "category"');
        $query->setParameter('ids', Uuid::fromHexToBytesList($productIds), ArrayParameterType::STRING);
        $query->setParameter('sales_channel', Uuid::fromHexToBytes($salesChannelId));
        $query->setParameter('raw_sales_channel', $salesChannelId);

        //@TODO: exclude all products that have any mapped main_category but none in this sales_channel

        $toExclude = Uuid::fromBytesToHexList($query->executeQuery()->fetchFirstColumn());

        return array_filter($products, static function (array $product) use ($toExclude) {
            return !in_array($product['id'], $toExclude);
        });

    }
}