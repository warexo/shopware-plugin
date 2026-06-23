<?php declare(strict_types=1);

namespace Warexo\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartDataCollectorInterface;
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class NonStackableProductLineItemQuantityNormalizer implements CartDataCollectorInterface, CartProcessorInterface
{
    private const DATA_KEY = 'warexoNonStackableProductLineItemOriginalQuantities';
    private const SURCHARGE_QUANTITY_DATA_KEY_PREFIX = 'calculated-surcharge-';

    public function __construct(
        private readonly SalesChannelRepository $salesChannelProductRepository
    ) {
    }

    public function collect(CartDataCollection $data, Cart $original, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $lineItems = $this->getNonStackableProductLineItems($original->getLineItems());
        if ($lineItems === []) {
            return;
        }

        $productIds = [];
        foreach ($lineItems as $lineItem) {
            $referencedId = $lineItem->getReferencedId();
            if ($referencedId !== null) {
                $productIds[$referencedId] = $referencedId;
            }
        }

        if ($productIds === []) {
            return;
        }

        $products = $this->salesChannelProductRepository->search(new Criteria(array_values($productIds)), $context);
        $originalQuantities = $data->get(self::DATA_KEY);
        if (!is_array($originalQuantities)) {
            $originalQuantities = [];
        }

        foreach ($lineItems as $lineItem) {
            $referencedId = $lineItem->getReferencedId();
            $product = $referencedId !== null ? $products->get($referencedId) : null;
            if (!$product instanceof SalesChannelProductEntity) {
                continue;
            }

            $minPurchase = $product->getMinPurchase() ?? 1;
            if ($lineItem->getQuantity() >= $minPurchase) {
                continue;
            }

            $originalQuantities[$lineItem->getId()] = $lineItem->getQuantity();
            $this->setQuantity($lineItem, $minPurchase);
        }

        if ($originalQuantities !== []) {
            $data->set(self::DATA_KEY, $originalQuantities);
        }
    }

    public function process(CartDataCollection $data, Cart $original, Cart $toCalculate, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $originalQuantities = $data->get(self::DATA_KEY);
        if (!is_array($originalQuantities)) {
            $originalQuantities = [];
        }

        $this->restoreOriginalQuantities($data, $original->getLineItems(), $originalQuantities);
        $this->restoreOriginalQuantities($data, $toCalculate->getLineItems(), $originalQuantities);
    }

    /**
     * @return list<LineItem>
     */
    private function getNonStackableProductLineItems(LineItemCollection $lineItems): array
    {
        $matches = [];

        foreach ($lineItems as $lineItem) {
            if ($this->isNormalizableProductLineItem($lineItem)) {
                $matches[] = $lineItem;
            }

            array_push($matches, ...$this->getNonStackableProductLineItems($lineItem->getChildren()));
        }

        return $matches;
    }

    private function isNormalizableProductLineItem(LineItem $lineItem): bool
    {
        return $lineItem->getType() === LineItem::PRODUCT_LINE_ITEM_TYPE
            && (!$lineItem->isStackable() || $lineItem->hasPayloadValue('surcharge'));
    }

    /**
     * @param array<string, int> $originalQuantities
     */
    private function restoreOriginalQuantities(CartDataCollection $data, LineItemCollection $lineItems, array $originalQuantities): void
    {
        foreach ($this->getNonStackableProductLineItems($lineItems) as $lineItem) {
            $quantity = $this->resolveRestoredQuantity($data, $lineItem, $originalQuantities);
            if ($quantity === null) {
                continue;
            }

            $this->setQuantity($lineItem, $quantity);
        }
    }

    /**
     * @param array<string, int> $originalQuantities
     */
    private function resolveRestoredQuantity(CartDataCollection $data, LineItem $lineItem, array $originalQuantities): ?int
    {
        if ($lineItem->hasPayloadValue('surcharge')) {
            $surchargeQuantity = $data->get(self::SURCHARGE_QUANTITY_DATA_KEY_PREFIX . $lineItem->getId());
            if (is_int($surchargeQuantity) && $surchargeQuantity > 0) {
                return $surchargeQuantity;
            }

            return null;
        }

        $originalQuantity = $originalQuantities[$lineItem->getId()] ?? null;
        if (is_int($originalQuantity) && $originalQuantity > 0) {
            return $originalQuantity;
        }

        return null;
    }

    private function setNonStackableQuantity(LineItem $lineItem, int $quantity): void
    {
        $lineItem->setStackable(true);
        $lineItem->setQuantity($quantity);
        $lineItem->setStackable(false);
    }

    private function setQuantity(LineItem $lineItem, int $quantity): void
    {
        if ($lineItem->getQuantity() === $quantity) {
            return;
        }

        if ($lineItem->hasPayloadValue('surcharge')) {
            $lineItem->setStackable(true);
            $lineItem->setQuantity($quantity);

            return;
        }

        $this->setNonStackableQuantity($lineItem, $quantity);
    }
}
