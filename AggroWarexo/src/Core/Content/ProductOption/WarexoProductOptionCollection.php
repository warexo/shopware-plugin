<?php declare(strict_types=1);

namespace Warexo\Core\Content\ProductOption;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

class WarexoProductOptionCollection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return ProductOptionValueEntity::class;
    }
}