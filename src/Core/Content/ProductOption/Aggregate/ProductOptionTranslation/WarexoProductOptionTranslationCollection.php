<?php declare(strict_types=1);

namespace Warexo\Core\Content\ProductOption\Aggregate\ProductOptionTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class WarexoProductOptionTranslationCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return WarexoProductOptionTranslationEntity::class;
    }
}