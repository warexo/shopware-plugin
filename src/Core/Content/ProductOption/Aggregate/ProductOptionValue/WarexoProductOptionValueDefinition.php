<?php declare(strict_types=1);

namespace Warexo\Core\Content\ProductOption\Aggregate\ProductOptionValue;

use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\PriceField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;

use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslationsAssociationField;
use Warexo\Core\Content\ProductOption\Aggregate\ProductOptionValueTranslation\WarexoProductOptionValueTranslationDefinition;
use Warexo\Core\Content\ProductOption\WarexoProductOptionDefinition;

class WarexoProductOptionValueDefinition extends EntityDefinition
{
    public const ENTITY_NAME = 'warexo_product_option_value';

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
            (new IntField('position', 'position')),
            (new StringField('color_hex_code', 'colorHexCode')),
            (new FkField('warexo_product_option_id', 'productOptionId', WarexoProductOptionDefinition::class))->addFlags(new Required()),
            (new FkField('media_id', 'mediaId', MediaDefinition::class))->addFlags(new ApiAware()),
            (new ManyToOneAssociationField('productOption', 'warexo_product_option_id', WarexoProductOptionDefinition::class, 'id')),
            (new ManyToOneAssociationField('media', 'media_id', MediaDefinition::class, 'id', false))->addFlags(new ApiAware()),
            (new TranslatedField('name'))->addFlags(new ApiAware(), new Required()),
            (new TranslatedField('description'))->addFlags(new ApiAware()),
            (new TranslatedField('customFields'))->addFlags(new ApiAware()),
            (new JsonField('surcharge', 'surcharge'))->addFlags(new ApiAware()),
            (new TranslationsAssociationField(
                WarexoProductOptionValueTranslationDefinition::class,
                'warexo_product_option_value_id'
            ))->addFlags(new ApiAware(), new Required())
        ]);
    }
}