<?php declare(strict_types=1);

namespace Warexo\Extension\Content\Product;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ProductExtensionEntity extends Entity
{
    use EntityIdTrait;

    /**
    * @var string
    */
    protected $productId;

    /**
     * @var ProductEntity|null
     */
    protected $product;

    /**
    * @var int
    */
    protected $position;

    public function getProductId(): string
    {
        return $this->productId;
    }

    public function setProductId(string $productId): void
    {
        $this->productId = $productId;
    }

    public function getProduct(): ?ProductEntity
    {
        return $this->product;
    }

    public function setProduct(?ProductEntity $product): void
    {
        $this->product = $product;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }
}