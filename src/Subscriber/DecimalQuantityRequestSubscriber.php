<?php declare(strict_types=1);

namespace Warexo\Subscriber;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepository;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityFeatureDecider;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityRequestTransformer;
use Warexo\Extension\Content\Product\ProductExtensionEntity;

class DecimalQuantityRequestSubscriber implements EventSubscriberInterface
{
    private const ADD_ROUTE = 'frontend.checkout.line-item.add';
    private const UPDATE_ROUTE = 'frontend.checkout.line-items.update';
    private const CHANGE_QUANTITY_ROUTE = 'frontend.checkout.line-item.change-quantity';
    private const DECIMAL_PAYLOADS_ATTRIBUTE = 'warexoDecimalPayloads';

    public function __construct(
        private readonly DecimalQuantityFeatureDecider $featureDecider,
        private readonly DecimalQuantityRequestTransformer $requestTransformer,
        private readonly CartService $cartService,
        private readonly SalesChannelRepository $salesChannelProductRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', -10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest() || !$this->featureDecider->isEnabled()) {
            return;
        }

        $request = $event->getRequest();
        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        if (!$context instanceof SalesChannelContext) {
            return;
        }

        $route = $request->attributes->get('_route');

        if ($route === self::ADD_ROUTE || $route === self::UPDATE_ROUTE) {
            $this->transformLineItems($request, $context);

            return;
        }

        if ($route !== self::CHANGE_QUANTITY_ROUTE) {
            return;
        }

        $lineItemId = $request->attributes->get('id');
        if (!is_string($lineItemId) || !$this->isDecimalCartLineItem($context, $lineItemId)) {
            return;
        }

        $transformed = $this->requestTransformer->transform($request->request->get('quantity'));
        if ($transformed === null) {
            return;
        }

        $request->attributes->set('warexoDecimalQuantity', $transformed['decimalQuantity']);
        $request->request->set('quantity', $transformed['coreQuantity']);
    }

    private function transformLineItems(Request $request, SalesChannelContext $context): void
    {
        $lineItems = $request->request->all('lineItems');
        if (!is_array($lineItems)) {
            return;
        }

        $decimalPayloads = [];

        foreach ($lineItems as $key => $lineItem) {
            if (!is_array($lineItem)) {
                continue;
            }

            $identifier = is_string($key) ? $key : null;
            $decimalPayload = $this->resolveDecimalPayload($context, $identifier);
            if ($decimalPayload === null) {
                continue;
            }

            $transformed = $this->requestTransformer->transform($lineItem['quantity'] ?? null);
            if ($transformed === null) {
                continue;
            }

            $payload = $lineItem['payload'] ?? [];
            if (!is_array($payload)) {
                $payload = [];
            }

            $payload = array_merge($payload, $decimalPayload);
            $payload['warexoDecimalQuantity'] = $transformed['decimalQuantity'];
            $lineItem['payload'] = $payload;
            $lineItem['quantity'] = $transformed['coreQuantity'];
            $lineItems[$key] = $lineItem;

            if ($identifier !== null) {
                $decimalPayloads[$identifier] = $payload;
            }
        }

        $request->request->set('lineItems', $lineItems);
        $request->attributes->set(self::DECIMAL_PAYLOADS_ATTRIBUTE, $decimalPayloads);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public static function getDecimalPayloads(Request $request): array
    {
        $payloads = $request->attributes->get(self::DECIMAL_PAYLOADS_ATTRIBUTE);

        return is_array($payloads) ? $payloads : [];
    }

    /**
     * @return array<string, float|bool>|null
     */
    private function resolveDecimalPayload(SalesChannelContext $context, ?string $identifier): ?array
    {
        if ($identifier === null) {
            return null;
        }

        $cartLineItem = $this->getCartLineItem($context, $identifier);
        if ($cartLineItem instanceof LineItem && $this->requestTransformer->isTruthy($cartLineItem->getPayloadValue('warexoIsDecimalQuantity'))) {
            return $this->buildDecimalPayload([
                'warexoDecimalMinPurchase' => $cartLineItem->getPayloadValue('warexoDecimalMinPurchase'),
                'warexoDecimalMaxPurchase' => $cartLineItem->getPayloadValue('warexoDecimalMaxPurchase'),
                'warexoDecimalPurchaseSteps' => $cartLineItem->getPayloadValue('warexoDecimalPurchaseSteps'),
            ]);
        }

        $criteria = new Criteria([$identifier]);
        $criteria->addAssociation('warexoExtension');

        $product = $this->salesChannelProductRepository->search($criteria, $context)->first();
        if ($product === null) {
            return null;
        }

        $extension = $product->getExtension('warexoExtension');
        if (!$extension instanceof ProductExtensionEntity) {
            return null;
        }

        return $this->buildDecimalPayload([
            'warexoDecimalMinPurchase' => $extension->getMinPurchase(),
            'warexoDecimalMaxPurchase' => $extension->getMaxPurchase(),
            'warexoDecimalPurchaseSteps' => $extension->getPurchaseSteps(),
        ]);
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

    private function isDecimalCartLineItem(SalesChannelContext $context, string $lineItemId): bool
    {
        $lineItem = $this->getCartLineItem($context, $lineItemId);

        return $lineItem instanceof LineItem
            && $this->requestTransformer->isTruthy($lineItem->getPayloadValue('warexoIsDecimalQuantity'));
    }

    private function getCartLineItem(SalesChannelContext $context, string $lineItemId): ?LineItem
    {
        $cart = $this->cartService->getCart($context->getToken(), $context);

        $lineItem = $cart->getLineItems()->get($lineItemId);

        return $lineItem instanceof LineItem ? $lineItem : null;
    }
}