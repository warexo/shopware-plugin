<?php declare(strict_types=1);

namespace Warexo\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartDataCollectorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityFeatureDecider;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityRequestTransformer;
use Warexo\Extension\Content\Product\ProductExtensionEntity;

class DecimalQuantityCartCollector implements CartDataCollectorInterface
{
    public function __construct(
        private readonly DecimalQuantityFeatureDecider $featureDecider,
        private readonly DecimalQuantityMapper $quantityMapper,
        private readonly DecimalQuantityRequestTransformer $requestTransformer,
        private readonly SalesChannelRepository $salesChannelProductRepository
    ) {
    }

    public function collect(CartDataCollection $data, Cart $original, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $decimalLineItems = [];
        $businessUnitPrices = [];

        if (!$this->featureDecider->isEnabled($context->getSalesChannelId())) {
            $data->set(DecimalQuantityCartData::DATA_KEY, $decimalLineItems);
            $data->set(DecimalQuantityCartData::BUSINESS_UNIT_PRICE_DATA_KEY, $businessUnitPrices);

            return;
        }

        $lineItems = $this->getProductLineItems($original);
        $products = $this->loadProducts($lineItems, $context);

        foreach ($lineItems as $lineItem) {
            if (!$lineItem->isStackable() || $lineItem->hasPayloadValue('surcharge')) {
                continue;
            }

            $referencedId = $lineItem->getReferencedId();
            $product = $referencedId !== null ? ($products[$referencedId] ?? null) : null;
            if (!$product instanceof SalesChannelProductEntity) {
                continue;
            }

            $lineItemData = $this->buildLineItemData($lineItem, $product);
            if ($lineItemData === null) {
                continue;
            }

            $this->synchronizeLineItem($lineItem, $lineItemData);

            $decimalLineItems[$lineItem->getId()] = $lineItemData;
            $businessUnitPrices[$lineItem->getId()] = $lineItemData['businessUnitPrice'];
        }

        $data->set(DecimalQuantityCartData::DATA_KEY, $decimalLineItems);
        $data->set(DecimalQuantityCartData::BUSINESS_UNIT_PRICE_DATA_KEY, $businessUnitPrices);
    }

    /**
     * @return list<LineItem>
     */
    private function getProductLineItems(Cart $cart): array
    {
        $lineItems = [];

        foreach ($cart->getLineItems()->filterFlatByType(LineItem::PRODUCT_LINE_ITEM_TYPE) as $lineItem) {
            if ($lineItem instanceof LineItem) {
                $lineItems[] = $lineItem;
            }
        }

        return $lineItems;
    }

    /**
     * @param list<LineItem> $lineItems
     *
     * @return array<string, SalesChannelProductEntity>
     */
    private function loadProducts(array $lineItems, SalesChannelContext $context): array
    {
        $productIds = [];
        foreach ($lineItems as $lineItem) {
            $referencedId = $lineItem->getReferencedId();
            if ($referencedId !== null) {
                $productIds[$referencedId] = $referencedId;
            }
        }

        if ($productIds === []) {
            return [];
        }

        $criteria = new Criteria(array_values($productIds));
        $criteria->addAssociation('warexoExtension');
        $criteria->addAssociation('prices');

        $products = [];
        foreach ($this->salesChannelProductRepository->search($criteria, $context)->getEntities() as $product) {
            if ($product instanceof SalesChannelProductEntity) {
                $products[$product->getId()] = $product;
            }
        }

        return $products;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildLineItemData(LineItem $lineItem, SalesChannelProductEntity $product): ?array
    {
        $extension = $product->getExtension('warexoExtension');
        $payloadMarksDecimal = $this->requestTransformer->isTruthy($lineItem->getPayloadValue('warexoIsDecimalQuantity'));
        $extensionMarksDecimal = $extension instanceof ProductExtensionEntity && $this->isDecimalExtension($extension);
        if (!$payloadMarksDecimal && !$extensionMarksDecimal) {
            return null;
        }

        $decimalQuantity = $this->normalizePositiveNumber($lineItem->getPayloadValue('warexoDecimalQuantity'))
            ?? $this->quantityMapper->fromCoreQuantity($lineItem->getQuantity());
        if ($decimalQuantity <= 0.0) {
            return null;
        }

        $decimalQuantity = round($decimalQuantity, DecimalQuantityMapper::SCALE);
        $selectedPrice = $this->resolveLineItemPrice($product, $decimalQuantity);

        return [
            'productId' => $product->getId(),
            'decimalQuantity' => $decimalQuantity,
            'coreQuantity' => $this->quantityMapper->toCoreQuantity($decimalQuantity),
            'minPurchase' => $this->resolveDecimalMinPurchase($lineItem, $extension),
            'maxPurchase' => $this->resolveDecimalMaxPurchase($lineItem, $extension, $product),
            'purchaseSteps' => $this->resolveDecimalPurchaseSteps($lineItem, $extension),
            'businessUnitPrice' => (float) $selectedPrice->getUnitPrice(),
            'listPrice' => $selectedPrice->getListPrice()?->getPrice(),
            'regulationPrice' => $selectedPrice->getRegulationPrice()?->getPrice(),
            'referencePriceDefinition' => $selectedPrice->getReferencePrice() !== null ? [
                'purchaseUnit' => $selectedPrice->getReferencePrice()->getPurchaseUnit(),
                'referenceUnit' => $selectedPrice->getReferencePrice()->getReferenceUnit(),
                'unitName' => $selectedPrice->getReferencePrice()->getUnitName(),
            ] : null,
        ];
    }

    private function resolveLineItemPrice(SalesChannelProductEntity $product, float $decimalQuantity): CalculatedPrice
    {
        $prices = $product->getCalculatedPrices();
        if ($prices->count() === 0) {
            return $product->getCalculatedPrice();
        }

        $price = $prices->first() ?? $product->getCalculatedPrice();
        foreach ($prices as $tierPrice) {
            if ($decimalQuantity < $tierPrice->getQuantity()) {
                break;
            }

            $price = $tierPrice;
        }

        return $price;
    }

    /**
     * @param array<string, mixed> $lineItemData
     */
    private function synchronizeLineItem(LineItem $lineItem, array $lineItemData): void
    {
        $coreQuantity = (int) $lineItemData['coreQuantity'];
        if ($lineItem->getQuantity() !== $coreQuantity) {
            $lineItem->setQuantity($coreQuantity);
        }

        $lineItem->setPayloadValue('warexoIsDecimalQuantity', true);
        $lineItem->setPayloadValue('warexoDecimalQuantity', $lineItemData['decimalQuantity']);
        $lineItem->setPayloadValue('warexoCoreQuantity', $coreQuantity);
        $this->setNullablePayloadValue($lineItem, 'warexoDecimalMinPurchase', $lineItemData['minPurchase'] ?? null);
        $this->setNullablePayloadValue($lineItem, 'warexoDecimalMaxPurchase', $lineItemData['maxPurchase'] ?? null);
        $this->setNullablePayloadValue($lineItem, 'warexoDecimalPurchaseSteps', $lineItemData['purchaseSteps'] ?? null);
    }

    private function setNullablePayloadValue(LineItem $lineItem, string $key, mixed $value): void
    {
        if ($value === null) {
            return;
        }

        $lineItem->setPayloadValue($key, $value);
    }

    private function resolveDecimalMinPurchase(LineItem $lineItem, ?ProductExtensionEntity $extension): float
    {
        return $this->normalizePositiveNumber($lineItem->getPayloadValue('warexoDecimalMinPurchase'))
            ?? $this->normalizePositiveNumber($extension?->getMinPurchase())
            ?? 1.0;
    }

    private function resolveDecimalMaxPurchase(LineItem $lineItem, ?ProductExtensionEntity $extension, SalesChannelProductEntity $product): ?float
    {
        return $this->normalizePositiveNumber($lineItem->getPayloadValue('warexoDecimalMaxPurchase'))
            ?? $this->normalizePositiveNumber($extension?->getMaxPurchase())
            ?? $this->normalizePositiveNumber($product->getCalculatedMaxPurchase() / DecimalQuantityMapper::FACTOR);
    }

    private function resolveDecimalPurchaseSteps(LineItem $lineItem, ?ProductExtensionEntity $extension): float
    {
        return $this->normalizePositiveNumber($lineItem->getPayloadValue('warexoDecimalPurchaseSteps'))
            ?? $this->normalizePositiveNumber($extension?->getPurchaseSteps())
            ?? 1.0;
    }

    private function isDecimalExtension(ProductExtensionEntity $extension): bool
    {
        return $extension->getStock() !== null
            || $extension->getMinPurchase() !== null
            || $extension->getMaxPurchase() !== null
            || $extension->getPurchaseSteps() !== null;
    }

    private function normalizePositiveNumber(mixed $value): ?float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', trim($value));
            if ($value === '' || !is_numeric($value)) {
                return null;
            }
        }

        if (!is_int($value) && !is_float($value) && !is_string($value)) {
            return null;
        }

        $value = (float) $value;

        return $value > 0.0 ? $value : null;
    }
}
