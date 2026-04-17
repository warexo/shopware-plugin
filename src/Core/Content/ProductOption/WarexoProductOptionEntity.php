<?php declare(strict_types=1);

namespace Warexo\Core\Content\ProductOption;

use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Warexo\Core\Content\ProductOption\Aggregate\ProductOptionValue\WarexoProductOptionValueCollection;

class WarexoProductOptionEntity extends Entity
{
    use EntityIdTrait;

    protected ?string $displayType;
    protected ?int $position;
    protected ?string $ident;
    protected ?string $name = null;
    protected ?string $description = null;
    protected ?WarexoProductOptionValueCollection $productOptionValues = null;
    protected ?ProductCollection $products = null;
    protected ?array $productIds;

    public function getDisplayType(): ?string
    {
        return $this->displayType;
    }

    public function setDisplayType(?string $displayType): void
    {
        $this->displayType = $displayType;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(?int $position): void
    {
        $this->position = $position;
    }

    public function getIdent(): ?string
    {
        return $this->ident;
    }

    public function setIdent(?string $ident): void
    {
        $this->ident = $ident;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return WarexoProductOptionValueCollection|null
     */
    public function getProductOptionValues(): ?WarexoProductOptionValueCollection
    {
        return $this->productOptionValues;
    }

    /**
     * @param WarexoProductOptionValueCollection $productOptionValues
     */
    public function setProductOptionValues(WarexoProductOptionValueCollection $productOptionValues): void
    {
        $this->productOptionValues = $productOptionValues;
    }

    /**
     * @return ProductCollection|null
     */
    public function getProducts(): ?ProductCollection
    {
        return $this->products;
    }

    /**
     * @param ProductCollection $products
     */
    public function setProducts(ProductCollection $products): void
    {
        $this->products = $products;
    }

    /**
     * @return array|null
     */
    public function getProductIds(): ?array
    {
        return $this->productIds;
    }

    /**
     * @param array|null $productIds
     */
    public function setProductIds(?array $productIds): void
    {
        $this->productIds = $productIds;
    }
}