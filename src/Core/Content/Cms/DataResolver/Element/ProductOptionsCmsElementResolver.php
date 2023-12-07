<?php

namespace Warexo\Core\Content\Cms\DataResolver\Element;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Product\Cms\AbstractProductDetailCmsElementResolver;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductDefinition;
use Warexo\Core\Content\Cms\SalesChannel\Struct\ProductOptionsStruct;

class ProductOptionsCmsElementResolver extends AbstractProductDetailCmsElementResolver
{
    public function getType(): string
    {
        return 'product-options';
    }


    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $productOptions = new ProductOptionsStruct();
        $product = null;
        $productConfig = $slot->getFieldConfig()->get('product');
        if ($productConfig) {
            if ($productConfig->isMapped() && $resolverContext instanceof EntityResolverContext) {
                $product = $this->resolveEntityValue($resolverContext->getEntity(), $productConfig->getStringValue());
            }

            if ($productConfig->isStatic()) {
                $product = $this->getSlotProduct($slot, $result, $productConfig->getStringValue());
            }

            if ($product !== null) {
                $productOptions->setProductId($product->getId());
                $productOptions->setProductOptions($product->getExtension('warexoProductOptions'));
            }
        }

        $slot->setData($productOptions);
    }
}