<?php declare(strict_types=1);

namespace Warexo\Core\Content\ProductOption\Aggregate\ProductOptionValueTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;
use Warexo\Core\Content\ProductOptionValue\ProductOptionValueEntity;

class WarexoProductOptionValueTranslationEntity extends TranslationEntity
{
    use EntityCustomFieldsTrait;
    protected string $productOptionValueId;

    protected ProductOptionValueEntity $productOptionValue;

    protected ?string $name;
    protected ?string $description;

    /**
     * @return string
     */
    public function getProductOptionValueId(): string
    {
        return $this->productOptionValueId;
    }

    /**
     * @param string $productOptionValueId
     */
    public function setProductOptionValueId(string $productOptionValueId): void
    {
        $this->productOptionValueId = $productOptionValueId;
    }

    /**
     * @return ProductOptionValueEntity
     */
    public function getProductOptionValue(): ProductOptionValueEntity
    {
        return $this->productOptionValue;
    }

    /**
     * @param ProductOptionValueEntity $productOptionValue
     */
    public function setProductOptionValue(ProductOptionValueEntity $productOptionValue): void
    {
        $this->productOptionValue = $productOptionValue;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     */
    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }


}