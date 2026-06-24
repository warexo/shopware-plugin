<?php declare(strict_types=1);

namespace Warexo\Subscriber;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityFeatureDecider;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityRequestTransformer;
use Warexo\Extension\Content\Product\ProductExtensionEntity;

class DecimalQuantityRequestSubscriber implements EventSubscriberInterface
{
    private const CHANGE_QUANTITY_ROUTE = 'frontend.checkout.line-item.change-quantity';
    private const DECIMAL_PAYLOADS_ATTRIBUTE = 'warexoDecimalPayloads';
    private const DECIMAL_ADD_COUNT_ATTRIBUTE = 'warexoDecimalAddCount';

    public function __construct(
        private readonly DecimalQuantityFeatureDecider $featureDecider,
        private readonly DecimalQuantityRequestTransformer $requestTransformer,
        private readonly DecimalQuantityMapper $quantityMapper,
        private readonly CartService $cartService,
        private readonly EntityRepository $productRepository,
        private readonly SalesChannelRepository $salesChannelProductRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onKernelController', -12],
        ];
    }

    public function onKernelController(ControllerEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->attributes->get('_route');
        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        $salesChannelContext = $context instanceof SalesChannelContext ? $context : null;
        if (!$this->isEnabledForContext($salesChannelContext)) {
            return;
        }

        if ($route !== self::CHANGE_QUANTITY_ROUTE && $this->hasLineItemsPayload($request)) {
            $this->transformLineItems($request, $salesChannelContext);

            return;
        }

        if ($route !== self::CHANGE_QUANTITY_ROUTE) {
            return;
        }

        if (!$salesChannelContext instanceof SalesChannelContext) {
            return;
        }

        $lineItemId = $request->attributes->get('id');
        if (!is_string($lineItemId)) {
            return;
        }

        $cartLineItem = $this->getCartLineItem($salesChannelContext, $lineItemId);
        if (!$cartLineItem instanceof LineItem) {
            return;
        }

        if ($this->requestTransformer->isTruthy($cartLineItem->getPayloadValue('warexoIsDecimalQuantity'))) {
            $transformed = $this->transformDecimalCartQuantity($cartLineItem, $request->request->get('quantity'));
            if ($transformed === null) {
                return;
            }

            $request->attributes->set('warexoDecimalQuantity', $transformed['decimalQuantity']);
            $request->attributes->set('quantity', $transformed['coreQuantity']);
            $request->request->set('quantity', $transformed['coreQuantity']);

            return;
        }

        $quantity = $this->transformCoreCartQuantity($cartLineItem, $request->request->get('quantity'));
        if ($quantity === null) {
            return;
        }

        $request->attributes->set('quantity', $quantity);
        $request->request->set('quantity', $quantity);
    }

    private function transformLineItems(Request $request, ?SalesChannelContext $context): void
    {
        $lineItems = $request->request->all('lineItems');
        if (!is_array($lineItems)) {
            return;
        }

        $decimalPayloads = [];
        $decimalAddCount = 0.0;

        $lineItems = $this->transformRequestLineItems($lineItems, $context, $decimalPayloads, $decimalAddCount);

        $request->request->set('lineItems', $lineItems);
        $request->attributes->set(self::DECIMAL_PAYLOADS_ATTRIBUTE, $decimalPayloads);

        if ($decimalAddCount > 0.0) {
            $request->attributes->set(self::DECIMAL_ADD_COUNT_ATTRIBUTE, round($decimalAddCount, 3));
        }
    }

    /**
     * @param array<mixed> $lineItems
     * @param array<string, array<string, float|bool>> $decimalPayloads
     *
     * @return array<mixed>
     */
    private function transformRequestLineItems(array $lineItems, ?SalesChannelContext $context, array &$decimalPayloads, float &$decimalAddCount): array
    {
        foreach ($lineItems as $key => $lineItem) {
            if (!is_array($lineItem)) {
                continue;
            }

            if (isset($lineItem['children']) && is_array($lineItem['children'])) {
                $lineItem['children'] = $this->transformRequestLineItems(
                    $lineItem['children'],
                    $context,
                    $decimalPayloads,
                    $decimalAddCount
                );
            }

            $lineItemId = is_string($key) ? $key : null;
            $productId = isset($lineItem['referencedId']) && is_string($lineItem['referencedId'])
                ? $lineItem['referencedId']
                : $lineItemId;

            $decimalPayload = $this->resolveDecimalPayload($lineItemId, $productId, $context);
            if ($decimalPayload === null) {
                $quantity = $this->transformCoreProductQuantity($productId, $context, $lineItem['quantity'] ?? null);
                if ($quantity !== null) {
                    $lineItem['quantity'] = $quantity;
                }

                $lineItems[$key] = $lineItem;

                continue;
            }

            $quantity = $this->normalizeRequestedQuantity($lineItem['quantity'] ?? null);
            if ($quantity !== null) {
                $quantity = $this->normalizeScaledDefaultQuantity($quantity, $decimalPayload);
                $quantity = $this->snapQuantityToPurchaseInterval(
                    $quantity,
                    $decimalPayload['warexoDecimalMinPurchase'] ?? null,
                    $decimalPayload['warexoDecimalPurchaseSteps'] ?? null,
                    $decimalPayload['warexoDecimalMaxPurchase'] ?? null
                );
            }

            $transformed = $this->requestTransformer->transform($quantity);
            if ($transformed === null) {
                $lineItems[$key] = $lineItem;

                continue;
            }

            $requestPayload = $decimalPayload;
            $requestPayload['warexoDecimalQuantity'] = $transformed['decimalQuantity'];
            $payload = $this->withoutInternalPayloadValues($requestPayload);

            if (isset($lineItem['payload']) && is_array($lineItem['payload'])) {
                $lineItem['payload'] = array_merge($lineItem['payload'], $payload);
            } elseif (!isset($lineItem['payload']) || (is_string($lineItem['payload']) && trim($lineItem['payload']) === '')) {
                $lineItem['payload'] = $payload;
            }

            $lineItem['quantity'] = $transformed['coreQuantity'];
            $lineItems[$key] = $lineItem;
            $decimalAddCount += $transformed['decimalQuantity'];

            if ($lineItemId !== null) {
                $decimalPayloads[$lineItemId] = $requestPayload;
            }
        }

        return $lineItems;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getDecimalPayloads(Request $request): array
    {
        $payloads = $request->attributes->get(self::DECIMAL_PAYLOADS_ATTRIBUTE);

        return is_array($payloads) ? $payloads : [];
    }

    public static function getDecimalAddCount(Request $request): ?float
    {
        $count = $request->attributes->get(self::DECIMAL_ADD_COUNT_ATTRIBUTE);

        return is_float($count) || is_int($count) ? (float) $count : null;
    }

    /**
     * @return array<string, float|bool>|null
     */
    private function resolveDecimalPayload(?string $lineItemId, ?string $productId, ?SalesChannelContext $context): ?array
    {
        if ($lineItemId === null && $productId === null) {
            return null;
        }

        if ($lineItemId !== null && $context instanceof SalesChannelContext) {
            $cartLineItem = $this->getCartLineItem($context, $lineItemId);
            if ($cartLineItem instanceof LineItem && $this->requestTransformer->isTruthy($cartLineItem->getPayloadValue('warexoIsDecimalQuantity'))) {
                return $this->buildDecimalPayload([
                    'warexoDecimalMinPurchase' => $cartLineItem->getPayloadValue('warexoDecimalMinPurchase'),
                    'warexoDecimalMaxPurchase' => $cartLineItem->getPayloadValue('warexoDecimalMaxPurchase'),
                    'warexoDecimalPurchaseSteps' => $cartLineItem->getPayloadValue('warexoDecimalPurchaseSteps'),
                    '_warexoCoreMinPurchase' => $cartLineItem->getQuantityInformation()?->getMinPurchase(),
                    '_warexoCoreMaxPurchase' => $cartLineItem->getQuantityInformation()?->getMaxPurchase(),
                ]);
            }
        }

        if ($productId === null) {
            return null;
        }

        $product = $this->loadProduct($productId, $context);
        if (!$product instanceof ProductEntity) {
            return null;
        }

        $extension = $product->getExtension('warexoExtension');
        if (!$extension instanceof ProductExtensionEntity) {
            return null;
        }

        return $this->buildDecimalPayload([
            'warexoDecimalMinPurchase' => $extension->getMinPurchase(),
            'warexoDecimalMaxPurchase' => $extension->getMaxPurchase() ?? $this->resolveCalculatedMaxPurchase($product),
            'warexoDecimalPurchaseSteps' => $extension->getPurchaseSteps(),
            '_warexoCoreMinPurchase' => $product->getMinPurchase(),
            '_warexoCoreMaxPurchase' => $extension->getMaxPurchase() !== null ? $product->getMaxPurchase() : $product->getCalculatedMaxPurchase(),
        ]);
    }

    private function loadProduct(string $productId, ?SalesChannelContext $context): ?ProductEntity
    {
        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('warexoExtension');

        if ($context instanceof SalesChannelContext) {
            $product = $this->salesChannelProductRepository->search($criteria, $context)->first();

            return $product instanceof ProductEntity ? $product : null;
        }

        $product = $this->productRepository->search($criteria, Context::createDefaultContext())->first();

        return $product instanceof ProductEntity ? $product : null;
    }

    private function resolveCalculatedMaxPurchase(ProductEntity $product): ?float
    {
        $calculatedMaxPurchase = $product->get('calculatedMaxPurchase');
        if (!is_int($calculatedMaxPurchase) && !is_float($calculatedMaxPurchase)) {
            return null;
        }

        return $calculatedMaxPurchase / 1000;
    }

    /**
     * @param array<string, mixed> $values
     *
     * @return array<string, float|bool>
     */
    private function buildDecimalPayload(array $values): array
    {
        $payload = [
            'warexoIsDecimalQuantity' => true,
        ];

        foreach ($values as $key => $value) {
            if (is_float($value) || is_int($value)) {
                $payload[$key] = (float) $value;
            }
        }

        return $payload;
    }

    /**
     * Product listing add-to-cart buttons often submit product.minPurchase as a hidden
     * value. In decimal mode that field is core-scaled, so turn only that exact default
     * back into its decimal value before transforming the request for the cart.
     *
     * @param array<string, mixed> $decimalPayload
     */
    private function normalizeScaledDefaultQuantity(float $quantity, array $decimalPayload): float
    {
        $decimalMinPurchase = $this->normalizePositiveNumber($decimalPayload['warexoDecimalMinPurchase'] ?? null);
        $coreMinPurchase = $this->normalizePositiveNumber($decimalPayload['_warexoCoreMinPurchase'] ?? null);

        if ($decimalMinPurchase === null || $coreMinPurchase === null) {
            return $quantity;
        }

        if (!$this->floatsEqual($coreMinPurchase, (float) $this->quantityMapper->toCoreQuantity($decimalMinPurchase))) {
            return $quantity;
        }

        if (!$this->floatsEqual($quantity, $coreMinPurchase)) {
            return $quantity;
        }

        return $decimalMinPurchase;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function withoutInternalPayloadValues(array $payload): array
    {
        foreach (array_keys($payload) as $key) {
            if (str_starts_with((string) $key, '_warexo')) {
                unset($payload[$key]);
            }
        }

        return $payload;
    }

    private function floatsEqual(float $left, float $right): bool
    {
        return abs($left - $right) < 0.0001;
    }

    /**
     * @return array{decimalQuantity: float, coreQuantity: int}|null
     */
    private function transformDecimalCartQuantity(LineItem $lineItem, mixed $rawQuantity): ?array
    {
        $quantity = $this->normalizeRequestedQuantity($rawQuantity);
        if ($quantity === null) {
            return null;
        }

        $quantity = $this->snapQuantityToPurchaseInterval(
            $quantity,
            $lineItem->getPayloadValue('warexoDecimalMinPurchase'),
            $lineItem->getPayloadValue('warexoDecimalPurchaseSteps'),
            $lineItem->getPayloadValue('warexoDecimalMaxPurchase')
        );

        return $this->requestTransformer->transform(round($quantity, DecimalQuantityMapper::SCALE));
    }

    private function transformCoreCartQuantity(LineItem $lineItem, mixed $rawQuantity): ?int
    {
        $quantity = $this->normalizeRequestedQuantity($rawQuantity);
        if ($quantity === null || $quantity <= 0.0) {
            return null;
        }

        $quantityInformation = $lineItem->getQuantityInformation();
        $quantity = $this->snapQuantityToPurchaseInterval(
            $quantity,
            $quantityInformation?->getMinPurchase(),
            $quantityInformation?->getPurchaseSteps(),
            $quantityInformation?->getMaxPurchase()
        );

        return max(1, (int) round($quantity));
    }

    private function transformCoreProductQuantity(?string $productId, ?SalesChannelContext $context, mixed $rawQuantity): ?int
    {
        if ($productId === null) {
            return null;
        }

        $product = $this->loadProduct($productId, $context);
        if (!$product instanceof ProductEntity) {
            return null;
        }

        $quantity = $this->normalizeRequestedQuantity($rawQuantity);
        if ($quantity === null || $quantity <= 0.0) {
            return null;
        }

        $quantity = $this->snapQuantityToPurchaseInterval(
            $quantity,
            $product->get('minPurchase'),
            $product->get('purchaseSteps'),
            $product->get('maxPurchase')
        );

        return max(1, (int) round($quantity));
    }

    private function normalizeRequestedQuantity(mixed $rawQuantity): ?float
    {
        if (is_int($rawQuantity) || is_float($rawQuantity)) {
            return (float) $rawQuantity;
        }

        if (!is_string($rawQuantity)) {
            return null;
        }

        $normalized = str_replace(',', '.', trim($rawQuantity));
        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    private function snapQuantityToPurchaseInterval(float $quantity, mixed $minPurchase, mixed $purchaseSteps, mixed $maxPurchase): float
    {
        $minPurchase = $this->normalizePositiveNumber($minPurchase) ?? 1.0;
        $purchaseSteps = $this->normalizePositiveNumber($purchaseSteps) ?? 1.0;
        $maxPurchase = $this->normalizePositiveNumber($maxPurchase);

        $steps = round(($quantity - $minPurchase) / $purchaseSteps);
        $quantity = $minPurchase + ($steps * $purchaseSteps);
        $quantity = max($minPurchase, $quantity);

        if ($maxPurchase !== null) {
            $maxPurchase = max($minPurchase, $maxPurchase);
            if ($quantity > $maxPurchase) {
                $quantity = $minPurchase + (floor(($maxPurchase - $minPurchase) / $purchaseSteps) * $purchaseSteps);
            }
        }

        return $quantity;
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

    private function getCartLineItem(SalesChannelContext $context, string $lineItemId): ?LineItem
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);

        $lineItem = $cart->getLineItems()->get($lineItemId);

        return $lineItem instanceof LineItem ? $lineItem : null;
    }

    private function hasLineItemsPayload(Request $request): bool
    {
        $lineItems = $request->request->all('lineItems');

        return is_array($lineItems) && $lineItems !== [];
    }

    private function isEnabledForContext(?SalesChannelContext $context): bool
    {
        if ($context instanceof SalesChannelContext) {
            return $this->featureDecider->isEnabled($context->getSalesChannelId());
        }

        return $this->featureDecider->isEnabled();
    }
}
