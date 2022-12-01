<?php declare(strict_types=1);

namespace Warexo\Core\Content\ProductOption\Aggregate\ProductOptionValue;

use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\Struct\Struct;

class WarexoProductOptionValueEntity extends Entity
{
    use EntityIdTrait;

    protected string $productOptionId;
    protected ?ProductOptionEntity $productOption = null;

    protected ?string $mediaId;
    protected ?MediaEntity $media = null;

    protected ?string $colorHexCode;
    protected string $name;
    protected ?string $description;

    protected ?array $surcharge = null;

    /**
     * @return string
     */
    public function getProductOptionId(): string
    {
        return $this->productOptionId;
    }

    /**
     * @param string $productOptionId
     */
    public function setProductOptionId(string $productOptionId): void
    {
        $this->productOptionId = $productOptionId;
    }

    /**
     * @return ProductOptionEntity
     */
    public function getProductOption(): ProductOptionEntity
    {
        return $this->productOption;
    }

    /**
     * @param ProductOptionEntity $productOption
     */
    public function setProductOption(ProductOptionEntity $productOption): void
    {
        $this->productOption = $productOption;
    }

    /**
     * @return string|null
     */
    public function getMediaId(): ?string
    {
        return $this->mediaId;
    }

    /**
     * @param string|null $mediaId
     */
    public function setMediaId(?string $mediaId): void
    {
        $this->mediaId = $mediaId;
    }

    /**
     * @return MediaEntity
     */
    public function getMedia(): ?MediaEntity
    {
        return $this->media;
    }

    /**
     * @param MediaEntity $media
     */
    public function setMedia(MediaEntity $media): void
    {
        $this->media = $media;
    }

    /**
     * @return array|null
     */
    public function getSurcharge(): ?array
    {
        return $this->surcharge;
    }

    /**
     * @param array|null $surcharge
     */
    public function setSurcharge(array $surcharge): void
    {
        $this->surcharge = $surcharge;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
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

    /**
     * @return string|null
     */
    public function getColorHexCode(): ?string
    {
        return $this->colorHexCode;
    }

    /**
     * @param string|null $colorHexCode
     */
    public function setColorHexCode(?string $colorHexCode): void
    {
        $this->colorHexCode = $colorHexCode;
    }

}