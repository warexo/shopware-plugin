<?php declare(strict_types=1);

namespace Warexo\Core\Content\ProductOption\Aggregate\ProductOptionValue;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class WarexoProductOptionValueCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return WarexoProductOptionValueEntity::class;
    }
}