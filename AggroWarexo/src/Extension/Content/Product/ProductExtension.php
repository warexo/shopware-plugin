<?php declare(strict_types=1);

namespace Warexo\Extension\Content\Product;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityExtension;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Inherited;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyIdField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Warexo\Core\Content\ProductOption\Aggregate\ProductProductOption\WarexoProductProductOptionDefinition;
use Warexo\Core\Content\ProductOption\WarexoProductOptionDefinition;

class ProductExtension extends EntityExtension
{
    public function extendFields(FieldCollection $collection): void
    {
        $collection->add(
            (new ManyToManyAssociationField(
                'warexoProductOptions',
                WarexoProductOptionDefinition::class,
                WarexoProductProductOptionDefinition::class,
                'product_id',
                'warexo_product_option_id'
            ))->addFlags(new Inherited())
        );
    }

    public function getDefinitionClass(): string
    {
        return ProductDefinition::class;
    }
}