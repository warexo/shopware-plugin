<?php declare(strict_types=1);

namespace Warexo\Extension\Content\Category;

use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class CategoryExtensionEntity extends Entity
{
    use EntityIdTrait;

    /**
    * @var string
    */
    protected $categoryId;

    /**
     * @var CategoryEntity|null
     */
    protected $category;

    /**
    * @var int
    */
    protected $position;

    public function getCategoryId(): string
    {
        return $this->categoryId;
    }

    public function setCategoryId(string $categoryId): void
    {
        $this->categoryId = $categoryId;
    }

    public function getCategory(): ?CategoryEntity
    {
        return $this->category;
    }

    public function setCategory(?CategoryEntity $category): void
    {
        $this->category = $category;
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