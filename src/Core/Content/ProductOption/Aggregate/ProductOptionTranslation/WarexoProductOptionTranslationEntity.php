<?php declare(strict_types=1);

namespace Warexo\Core\Content\ProductOption\Aggregate\ProductOptionTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;
use Warexo\Core\Content\ProductOption\WarexoProductOptionEntity;

class WarexoProductOptionTranslationEntity extends TranslationEntity
{
    use EntityCustomFieldsTrait;

    protected string $productOptionId;

    protected WarexoProductOptionEntity $productOption;

    protected ?string $name;
    protected ?string $description;

    public function getProductOptionId(): string
    {
        return $this->productOptionId;
    }

    public function setProductOptionId(string $productOptionId): void
    {
        $this->productOptionId = $productOptionId;
    }

    public function getProductOption(): WarexoProductOptionEntity
    {
        return $this->productOption;
    }

    public function setProductOption(WarexoProductOptionEntity $productOption): void
    {
        $this->productOption = $productOption;
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
}