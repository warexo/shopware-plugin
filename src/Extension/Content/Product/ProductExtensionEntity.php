<?php declare(strict_types=1);

namespace Warexo\Extension\Content\Product;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class ProductExtensionEntity extends Entity
{
    use EntityIdTrait;

    protected string $productId;

    protected ?ProductEntity $product = null;

    protected int $position = 0;

    protected ?float $stock = null;

    protected ?float $minPurchase = null;

    protected ?float $maxPurchase = null;

    protected ?float $purchaseSteps = null;

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

    public function getStock(): ?float
    {
        return $this->stock;
    }

    public function setStock(?float $stock): void
    {
        $this->stock = $stock;
    }

    public function getMinPurchase(): ?float
    {
        return $this->minPurchase;
    }

    public function setMinPurchase(?float $minPurchase): void
    {
        $this->minPurchase = $minPurchase;
    }

    public function getMaxPurchase(): ?float
    {
        return $this->maxPurchase;
    }

    public function setMaxPurchase(?float $maxPurchase): void
    {
        $this->maxPurchase = $maxPurchase;
    }

    public function getPurchaseSteps(): ?float
    {
        return $this->purchaseSteps;
    }

    public function setPurchaseSteps(?float $purchaseSteps): void
    {
        $this->purchaseSteps = $purchaseSteps;
    }
}