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
                if ($manufacturer) {
                    $gpsrInfo->setDescription($manufacturer->getTranslation('description') ?? '');
                    $customFields = $manufacturer->getCustomFields();
                    if ($customFields) {
                        $gpsrInfo->setCompany($customFields['warexo_gpsr_company'] ?? '');
                        $gpsrInfo->setAddress($customFields['warexo_gpsr_address'] ?? '');
                        $gpsrInfo->setCountry($customFields['warexo_gpsr_country'] ?? '');
                        $gpsrInfo->setZip($customFields['warexo_gpsr_zip'] ?? '');
                        $gpsrInfo->setCity($customFields['warexo_gpsr_city'] ?? '');
                        $gpsrInfo->setEmail($customFields['warexo_gpsr_email'] ?? '');     
                        $gpsrInfo->setUrl($customFields['warexo_gpsr_url'] ?? '');   
                        $gpsrInfo->setImporterCompany($customFields['warexo_gpsr_importer_company'] ?? '');
                        $gpsrInfo->setImporterAddress($customFields['warexo_gpsr_importer_address'] ?? '');
                        $gpsrInfo->setImporterUrl($customFields['warexo_gpsr_importer_url'] ?? '');
                        $gpsrInfo->setImporterEmail($customFields['warexo_gpsr_importer_email'] ?? '');
                        $gpsrInfo->setResponsiblePersonCompany($customFields['warexo_gpsr_responsible_person_company'] ?? '');
                        $gpsrInfo->setResponsiblePersonAddress($customFields['warexo_gpsr_responsible_person_address'] ?? '');
                        $gpsrInfo->setResponsiblePersonEmail($customFields['warexo_gpsr_responsible_person_email'] ?? '');
                    }                                
                }
            }
        }

        $slot->setData($gpsrInfo);
    }
}