<?php declare(strict_types=1);

namespace Warexo\Extension\Content\Category;

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

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }
}