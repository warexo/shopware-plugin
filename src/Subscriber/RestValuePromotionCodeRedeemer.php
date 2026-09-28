<?php declare(strict_types=1);

namespace Warexo\Subscriber;

use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Checkout\Promotion\Aggregate\PromotionIndividualCode\PromotionIndividualCodeCollection;
use Shopware\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Shopware\Core\Checkout\Promotion\PromotionException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RestValuePromotionCodeRedeemer implements EventSubscriberInterface
{
    /** @var \WeakMap<EntityWrittenEvent, array<string, array{orderId: string, restValue: float}>> */
    private \WeakMap $remainingBalances;

    /**
     * @param EntityRepository<PromotionIndividualCodeCollection> $individualCodeRepository
     */
    public function __construct(private readonly EntityRepository $individualCodeRepository)
    {
        $this->remainingBalances = new \WeakMap();
    }

    public static function getSubscribedEvents(): array
    {
        return [
            OrderEvents::ORDER_LINE_ITEM_WRITTEN_EVENT => [
                ['validateRestValueRedemptions', 100],
                ['persistRestValueRedemptions', -100],
            ],
        ];
    }

    public function validateRestValueRedemptions(EntityWrittenEvent $event): void
    {
        unset($this->remainingBalances[$event]);

        if ($event->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        $redemptions = $this->collectRestValueRedemptions($event);
        if ($redemptions === []) {
            return;
        }

        $criteria = (new Criteria())->addFilter(new EqualsAnyFilter('code', array_keys($redemptions)));
        $individualCodes = $this->individualCodeRepository->search($criteria, $event->getContext())->getEntities();
        $foundCodes = [];
        $updates = [];
        $balances = [];

        foreach ($individualCodes as $individualCode) {
            $normalizedCode = mb_strtolower($individualCode->getCode());
            if (!isset($redemptions[$normalizedCode])) {
                continue;
            }

            $foundCodes[$normalizedCode] = true;
            $redemption = $redemptions[$normalizedCode];
            $payload = $individualCode->getPayload();
            $currentRestValue = $this->normalizeRestValue($payload['restValue'] ?? null, false, true);

            if (($payload['orderId'] ?? null) === $redemption['orderId']) {
                if ($currentRestValue === null) {
                    throw PromotionException::codeAlreadyRedeemed($individualCode->getCode());
                }
                // Core replaces the payload even when this order is written again.
                $balances[$normalizedCode] = ['orderId' => $redemption['orderId'], 'restValue' => $currentRestValue];
                continue;
            }

            if ($currentRestValue === null || $currentRestValue <= 0.0
                || $redemption['restValue'] === null || $redemption['restValueCurrency'] === null
                || $redemption['redeemedCurrencyValue'] === null
                || abs($currentRestValue - $redemption['restValue']) > 0.00001) {
                throw PromotionException::codeAlreadyRedeemed($individualCode->getCode());
            }

            $redeemedValue = $redemption['redeemedCurrencyValue']
                * ($redemption['restValue'] / $redemption['restValueCurrency']);
            $balances[$normalizedCode] = [
                'orderId' => $redemption['orderId'],
                'restValue' => max(0.0, round($currentRestValue - $redeemedValue, 2)),
            ];

            if (array_key_exists('orderId', $payload)) {
                unset($payload['orderId'], $payload['customerId'], $payload['customerName']);

                $updates[] = [
                    'id' => $individualCode->getId(),
                    'payload' => $payload,
                ];
            }
        }

        foreach ($redemptions as $normalizedCode => $redemption) {
            if (!isset($foundCodes[$normalizedCode])) {
                throw PromotionException::codeAlreadyRedeemed($redemption['code']);
            }
        }

        if ($updates !== []) {
            // Core rejects a code carrying a previous orderId. Keep the balance while
            // removing only its prior redemption metadata before core runs.
            $this->individualCodeRepository->update($updates, $event->getContext());
        }
        $this->remainingBalances[$event] = $balances;
    }

    public function persistRestValueRedemptions(EntityWrittenEvent $event): void
    {
        $balances = $this->remainingBalances[$event] ?? [];
        unset($this->remainingBalances[$event]);
        if ($balances === []) {
            return;
        }

        $criteria = (new Criteria())->addFilter(new EqualsAnyFilter('code', array_keys($balances)));
        $individualCodes = $this->individualCodeRepository->search($criteria, $event->getContext())->getEntities();
        $updates = [];

        foreach ($individualCodes as $individualCode) {
            $normalizedCode = mb_strtolower($individualCode->getCode());
            if (!isset($balances[$normalizedCode])) {
                continue;
            }

            $payload = $individualCode->getPayload() ?? [];
            $payload['orderId'] = $balances[$normalizedCode]['orderId'];
            $payload['restValue'] = $balances[$normalizedCode]['restValue'];

            $updates[] = [
                'id' => $individualCode->getId(),
                'payload' => $payload,
            ];
        }

        if ($updates !== []) {
            // Keep the locally calculated balance available until Warexo replaces it
            // with its authoritative restValue.
            $this->individualCodeRepository->update($updates, $event->getContext());
        }
    }

    /**
     * @return array<string, array{code: string, orderId: string, restValue: ?float, restValueCurrency: ?float, redeemedCurrencyValue: ?float}>
     */
    private function collectRestValueRedemptions(EntityWrittenEvent $event): array
    {
        $redemptions = [];

        foreach ($event->getWriteResults() as $result) {
            $writePayload = $result->getPayload();
            if (($writePayload['type'] ?? null) !== PromotionProcessor::LINE_ITEM_TYPE) {
                continue;
            }

            $lineItemPayload = $writePayload['payload'] ?? null;
            if (!\is_array($lineItemPayload) || !array_key_exists('warexoRestValue', $lineItemPayload)) {
                continue;
            }

            $code = $lineItemPayload['code'] ?? null;
            $orderId = $writePayload['orderId'] ?? null;
            $restValue = $this->normalizeRestValue($lineItemPayload['warexoRestValue']);
            $restValueCurrency = $this->normalizeRestValue($lineItemPayload['warexoRestValueCurrency'] ?? null);
            $redeemedCurrencyValue = $this->normalizeRestValue(
                $this->getTotalPrice($writePayload['price'] ?? null),
                true
            );

            if (
                !\is_string($code)
                || $code === ''
                || !\is_string($orderId)
                || $orderId === ''
            ) {
                continue;
            }

            $normalizedCode = mb_strtolower($code);
            if (isset($redemptions[$normalizedCode])) {
                $previousValue = $redemptions[$normalizedCode]['redeemedCurrencyValue'];
                $redemptions[$normalizedCode]['redeemedCurrencyValue'] = $previousValue !== null && $redeemedCurrencyValue !== null
                    ? $previousValue + $redeemedCurrencyValue : null;
            } else {
                $redemptions[$normalizedCode] = [
                    'code' => $code,
                    'orderId' => $orderId,
                    'restValue' => $restValue,
                    'restValueCurrency' => $restValueCurrency,
                    'redeemedCurrencyValue' => $redeemedCurrencyValue,
                ];
            }
        }

        return $redemptions;
    }

    private function getTotalPrice(mixed $price): mixed
    {
        if ($price instanceof CalculatedPrice) {
            return $price->getTotalPrice();
        }

        if (\is_array($price)) {
            return $price['totalPrice'] ?? null;
        }

        return null;
    }

    private function normalizeRestValue(mixed $value, bool $absolute = false, bool $allowZero = false): ?float
    {
        if ((!\is_int($value) && !\is_float($value) && !\is_string($value)) || !is_numeric($value)) {
            return null;
        }

        $restValue = (float) $value;
        if ($absolute) {
            $restValue = abs($restValue);
        }

        return is_finite($restValue) && ($allowZero ? $restValue >= 0.0 : $restValue > 0.0) ? $restValue : null;
    }
}
