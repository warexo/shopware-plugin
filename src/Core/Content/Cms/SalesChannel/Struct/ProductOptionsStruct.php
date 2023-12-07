<?php

namespace Warexo\Core\Content\Cms\SalesChannel\Struct;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\Struct\Struct;

class ProductOptionsStruct extends Struct
{
    /**
     * @var string|null
     */
    protected $productId;

    /**
     * @var EntityCollection|null
     */
    protected $productOptions;

    public function getProductId(): ?string
    {
        return $this->productId;
    }

    public function setProductId(?string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProductOptions(): ?EntityCollection
    {
        return $this->productOptions;
    }

    public function setProductOptions(?EntityCollection $productOptions): void
    {
        $this->productOptions = $productOptions;
    }

}