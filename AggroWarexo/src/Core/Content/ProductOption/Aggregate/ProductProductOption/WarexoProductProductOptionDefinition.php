<?php declare(strict_types=1);

namespace Warexo\Core\Content\ProductOption\Aggregate\ProductProductOption;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;
use Warexo\Core\Content\ProductOption\WarexoProductOptionDefinition;

class WarexoProductProductOptionDefinition extends MappingEntityDefinition
{
    public const ENTITY_NAME = 'warexo_product_to_product_option';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new FkField('product_id', 'productId', ProductDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            (new FkField('warexo_product_option_id', 'productOptionId', WarexoProductOptionDefinition::class))->addFlags(new PrimaryKey(), new Required()),
            new ManyToOneAssociationField('products', 'product_id', ProductDefinition::class, 'id'),
            new ManyToOneAssociationField('productOptions', 'warexo_product_option_id', WarexoProductOptionDefinition::class, 'id')
        ]);
    }
}