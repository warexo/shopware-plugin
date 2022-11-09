<?php declare(strict_types=1);

namespace Warexo\Core\Content\ProductOption\Aggregate\ProductOptionTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityTranslationDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;

use Warexo\Core\Content\ProductOption\WarexoProductOptionDefinition;

class WarexoProductOptionTranslationDefinition extends EntityTranslationDefinition
{
    public const ENTITY_NAME = 'warexo_product_option_translation';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return WarexoProductOptionTranslationCollection::class;
    }

    public function getEntityClass(): string
    {
        return WarexoProductOptionTranslationEntity::class;
    }

    protected function getParentDefinitionClass(): string
    {
        return WarexoProductOptionDefinition::class;
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