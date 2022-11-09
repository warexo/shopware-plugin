<?php declare(strict_types=1);

namespace Warexo\Core\Content\ProductOption;

use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;

use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;

use Warexo\Core\Content\ProductOption\Aggregate\ProductOptionTranslation\WarexoProductOptionTranslationDefinition;
use Warexo\Core\Content\ProductOption\Aggregate\ProductOptionValue\WarexoProductOptionValueDefinition;
use Warexo\Core\Content\ProductOption\Aggregate\ProductOptionValue\WarexoProductOptionValueEntity;
use Warexo\Core\Content\ProductOption\Aggregate\ProductProductOption\WarexoProductProductOptionDefinition;

class WarexoProductOptionDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'warexo_product_option';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getEntityClass(): string
    {
        return WarexoProductOptionValueEntity::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags(new Required(), new PrimaryKey()),
            (new StringField('display_type', 'displayType')),
            (new IntField('position', 'position')),
            (new StringField('ident', 'ident')),
            (new OneToManyAssociationField('productOptionValues', WarexoProductOptionValueDefinition::class, 'warexo_product_option_id')),
            (new TranslatedField('name'))->addFlags(new ApiAware(), new Required()),
            (new TranslatedField('description'))->addFlags(new ApiAware()),
            (new TranslationsAssociationField(
                WarexoProductOptionTranslationDefinition::class,
                'warexo_product_option_id'
            ))->addFlags(new ApiAware(), new Required()),
            new ManyToManyAssociationField(
                'products',
                ProductDefinition::class,
                WarexoProductProductOptionDefinition::class,
                'warexo_product_option_id',
                'product_id'
            ),
        ]);
    }
}