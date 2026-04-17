<?php declare(strict_types=1);

namespace Warexo\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartDataCollectorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Warexo\Core\Content\ProductOption\Aggregate\ProductOptionValue\WarexoProductOptionValueEntity;
use Warexo\Core\Content\ProductOption\WarexoProductOptionCollection;
use Warexo\Core\Content\ProductOption\WarexoProductOptionEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;

class ProductOptionCartCollector implements CartDataCollectorInterface
{
    private EntityRepository $productRepository;
    private EntityRepository $productOptionRepository;
    private SalesChannelRepository $salesChannelProductRepository;

    public function __construct(EntityRepository $productRepository, EntityRepository $productOptionRepository, SalesChannelRepository $salesChannelProductRepository)
    {
        $this->productRepository = $productRepository;
        $this->productOptionRepository = $productOptionRepository;
        $this->salesChannelProductRepository = $salesChannelProductRepository;
    }

    public function collect(CartDataCollection $data, Cart $original, SalesChannelContext $context, CartBehavior $behavior): void
    {
        if ($data->has('optionValueSelections')) {
            return;
        }

        $selections = [];
        $businessUnitPrices = [];

        $lineItems = $original->getLineItems()->filterType(LineItem::PRODUCT_LINE_ITEM_TYPE);

        foreach ($lineItems as $lineItem) {
            $businessUnitPrice = $this->getLineItemBusinessUnitPrice($lineItem, $context);
            if ($businessUnitPrice !== null) {
                $businessUnitPrices[$lineItem->getId()] = $businessUnitPrice;
            }

            $product = $this->getLineItemProduct($lineItem, $context);
            $options = $product->getExtension('warexoProductOptions');
            if (!$options instanceof WarexoProductOptionCollection || count($options) === 0) {
                continue;
            }

            $selectedOptions = $lineItem->getPayloadValue('warexoProductOptions');
            if (!is_array($selectedOptions)) {
                continue;
            }

            $lineSelection = [];
            /** @var WarexoProductOptionEntity $option */
            foreach($options as $option)
            {
                if (!isset($selectedOptions[$option->getId()])) {
                    continue;
                }

                $value = $this->resolveSelectedOptionValue($option, $selectedOptions[$option->getId()]);
                if (!$value instanceof WarexoProductOptionValueEntity) {
                    continue;
                }

                $lineSelection[] = [
                    'option' => $option->getName(),
                    'value' => $value->getName(),
                    'surcharge' => $value->getSurcharge()
                ];
            }

            if (count($lineSelection)){
                $selections[$lineItem->getId()] = $lineSelection;
            }
        }
        $data->set('optionValueSelections', $selections);
        $data->set('warexoBusinessUnitPrices', $businessUnitPrices);
    }

    private function getLineItemProduct(LineItem $lineItem, SalesChannelContext $context) : ProductEntity
    {
        $criteria = new Criteria([$lineItem->getReferencedId()]);
        $criteria->addAssociation('warexoProductOptions');
        $criteria->addAssociation('warexoProductOptions.productOptionValues');
        $product = $this->productRepository->search($criteria, $context->getContext())->first();
        if ($product instanceof ProductEntity) {
            return $product;
        }

        throw new \RuntimeException('Line item has no product');
    }

    private function getLineItemBusinessUnitPrice(LineItem $lineItem, SalesChannelContext $context): ?float
    {
        $referencedId = $lineItem->getReferencedId();
        if ($referencedId === null) {
            return null;
        }

        $criteria = new Criteria([$referencedId]);
        $product = $this->salesChannelProductRepository->search($criteria, $context)->first();
        if (!$product instanceof SalesChannelProductEntity) {
            return null;
        }

        $calculatedPrice = $product->getCalculatedPrice();
        if ($calculatedPrice === null) {
            return null;
        }

        return (float) $calculatedPrice->getUnitPrice();
    }

    private function resolveSelectedOptionValue(WarexoProductOptionEntity $option, mixed $selectedValueId): ?WarexoProductOptionValueEntity
    {
        if (!is_string($selectedValueId)) {
            return null;
        }

        $optionValues = $option->getProductOptionValues();
        if ($optionValues === null) {
            return null;
        }

        foreach ($optionValues as $optionValue) {
            if (!$optionValue instanceof WarexoProductOptionValueEntity) {
                continue;
            }

            if ($optionValue->getId() !== $selectedValueId) {
                continue;
            }

            if ($optionValue->getProductOptionId() !== $option->getId()) {
                return null;
            }

            return $optionValue;
        }

        return null;
    }
}