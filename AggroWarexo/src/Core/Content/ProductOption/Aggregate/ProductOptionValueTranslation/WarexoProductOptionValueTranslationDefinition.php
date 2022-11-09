<?php declare(strict_types=1);

namespace Warexo\Core\Content\ProductOption\Aggregate\ProductOptionValueTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Warexo\Core\Content\ProductOption\Aggregate\ProductOptionValue\WarexoProductOptionValueDefinition;


class WarexoProductOptionValueTranslationDefinition extends EntityTranslationDefinition
{
    public const ENTITY_NAME = 'warexo_product_option_value_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return WarexoProductOptionValueTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return WarexoProductOptionValueTranslationEntity::class;
    }

    protected function getParentDefinitionClass(): string
    {
        return WarexoProductOptionValueDefinition::class;
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('name', 'name'))->addFlags(new Required()),
            (new StringField('description', 'description')),
            (new CustomFields())->addFlags(new ApiAware()),
        ]);
    }
}
{

}