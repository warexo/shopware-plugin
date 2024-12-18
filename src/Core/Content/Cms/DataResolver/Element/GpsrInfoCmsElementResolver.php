<?php

namespace Warexo\Core\Content\Cms\DataResolver\Element;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\EntityResolverContext;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Product\Cms\AbstractProductDetailCmsElementResolver;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductDefinition;
use Warexo\Core\Content\Cms\SalesChannel\Struct\GpsrInfoStruct;
use Warexo\Core\Content\Cms\SalesChannel\Struct\ProductOptionsStruct;

class GpsrInfoCmsElementResolver extends AbstractProductDetailCmsElementResolver
{
    public function getType(): string
    {
        return 'gpsr-info';
    }


    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $gpsrInfo = new GpsrInfoStruct();
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
                $manufacturer = $product->getManufacturer();
                if ($manufacturer && $manufacturer->getCustomFields()) {
                    $customFields = $manufacturer->getCustomFields();
                    $gpsrInfo->setCompany($customFields['warexo_gpsr_company']);
                    $gpsrInfo->setAddress($customFields['warexo_gpsr_address']);
                    $gpsrInfo->setCountry($customFields['warexo_gpsr_country']);
                    $gpsrInfo->setZip($customFields['warexo_gpsr_zip']);
                    $gpsrInfo->setCity($customFields['warexo_gpsr_city']);
                    $gpsrInfo->setEmail($customFields['warexo_gpsr_email']);
                }
            }
        }

        $slot->setData($gpsrInfo);
    }
}