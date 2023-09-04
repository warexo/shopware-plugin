<?php

namespace Warexo\Core\Content\Product\DataAbstractionLayer\StockUpdate;

use Shopware\Core\Framework\Context;
use Shopware\Core\Content\Product\DataAbstractionLayer\StockUpdate\AbstractStockUpdateFilter;

class StockUpdateFilter extends AbstractStockUpdateFilter
{
    public function filter(array $ids, Context $context): array
    {
        return [];
    }
}