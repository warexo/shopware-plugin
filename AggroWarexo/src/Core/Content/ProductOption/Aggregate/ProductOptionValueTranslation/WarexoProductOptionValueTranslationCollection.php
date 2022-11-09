<?php declare(strict_types=1);

namespace Warexo\Core\Content\ProductOption\Aggregate\ProductOptionValueTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class WarexoProductOptionValueTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return WarexoProductOptionValueTranslationEntity::class;
    }
}