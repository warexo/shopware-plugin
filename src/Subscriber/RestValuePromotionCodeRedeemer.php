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
    /**
     * @param EntityRepository<PromotionIndividualCodeCollection> $individualCodeRepository
     */
    public function __construct(private readonly EntityRepository $individualCodeRepository)
    {
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

        foreach ($individualCodes as $individualCode) {
            $normalizedCode = mb_strtolower($individualCode->getCode());
            if (!isset($redemptions[$normalizedCode])) {
                continue;
            }

            $foundCodes[$normalizedCode] = true;
            $redemption = $redemptions[$normalizedCode];
            $payload = $individualCode->getPayload();

            if (($payload['orderId'] ?? null) === $redemption['orderId']) {
                continue;
            }

            $currentRestValue = $this->normalizePositiveRestValue($payload['restValue'] ?? null);
            if ($currentRestValue === null || abs($currentRestValue - $redemption['restValue']) > 0.00001) {
                throw PromotionException::codeAlreadyRedeemed($individualCode->getCode());
            }

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
    }

    public function persistRestValueRedemptions(EntityWrittenEvent $event): void
    {
        if ($event->getContext()->getVersionId() !== Defaults::LIVE_VERSION) {
            return;
        }

        $redemptions = $this->collectRestValueRedemptions($event);
        if ($redemptions === []) {
            return;
        }

        $criteria = (new Criteria())->addFilter(new EqualsAnyFilter('code', array_keys($redemptions)));
        $individualCodes = $this->individualCodeRepository->search($criteria, $event->getContext())->getEntities();
        $updates = [];

        foreach ($individualCodes as $individualCode) {
            $normalizedCode = mb_strtolower($individualCode->getCode());
            if (!isset($redemptions[$normalizedCode])) {
                continue;
            }

            $redemption = $redemptions[$normalizedCode];
            $redeemedValue = $redemption['redeemedCurrencyValue']
                * ($redemption['restValue'] / $redemption['restValueCurrency']);
            $remainingValue = max(0.0, round($redemption['restValue'] - $redeemedValue, 2));
            $payload = $individualCode->getPayload() ?? [];
            $payload['orderId'] = $redemption['orderId'];
            $payload['restValue'] = $remainingValue;

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
     * @return array<string, array{code: string, orderId: string, restValue: float, restValueCurrency: float, redeemedCurrencyValue: float}>
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
            $restValue = $this->normalizePositiveRestValue($lineItemPayload['warexoRestValue']);
            $restValueCurrency = $this->normalizePositiveRestValue($lineItemPayload['warexoRestValueCurrency'] ?? null);
            $redeemedCurrencyValue = $this->normalizePositiveRestValue(
                $this->getTotalPrice($writePayload['price'] ?? null),
                true
            );

            if (
                !\is_string($code)
                || $code === ''
                || !\is_string($orderId)
                || $orderId === ''
                || $restValue === null
                || $restValueCurrency === null
                || $redeemedCurrencyValue === null
            ) {
                continue;
            }

            $normalizedCode = mb_strtolower($code);
            if (isset($redemptions[$normalizedCode])) {
                $redemptions[$normalizedCode]['redeemedCurrencyValue'] += $redeemedCurrencyValue;
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

    private function normalizePositiveRestValue(mixed $value, bool $absolute = false): ?float
    {
        if ((!\is_int($value) && !\is_float($value) && !\is_string($value)) || !is_numeric($value)) {
            return null;
        }

        $restValue = (float) $value;
        if ($absolute) {
            $restValue = abs($restValue);
        }

        return is_finite($restValue) && $restValue > 0.0 ? $restValue : null;
    }
}
