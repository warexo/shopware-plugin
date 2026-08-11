<?php declare(strict_types=1);

namespace Warexo\Core\Checkout\Promotion\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartDataCollectorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\AbsolutePriceDefinition;
use Shopware\Core\Checkout\Promotion\Aggregate\PromotionDiscount\PromotionDiscountEntity;
use Shopware\Core\Checkout\Promotion\Aggregate\PromotionIndividualCode\PromotionIndividualCodeCollection;
use Shopware\Core\Checkout\Promotion\Cart\PromotionItemBuilder;
use Shopware\Core\Checkout\Promotion\Cart\PromotionProcessor;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class RestValuePromotionCollector implements CartDataCollectorInterface
{
    /**
     * @param EntityRepository<PromotionIndividualCodeCollection> $individualCodeRepository
     */
    public function __construct(private readonly EntityRepository $individualCodeRepository)
    {
    }

    public function collect(CartDataCollection $data, Cart $original, SalesChannelContext $context, CartBehavior $behavior): void
    {
        if (!$data->has(PromotionProcessor::DATA_KEY)) {
            return;
        }

        $promotionItems = $data->get(PromotionProcessor::DATA_KEY);
        if (!$promotionItems instanceof LineItemCollection) {
            return;
        }

        $itemsByCode = $this->getIndividualPromotionItemsByCode($promotionItems);
        if ($itemsByCode === []) {
            return;
        }

        $criteria = (new Criteria())->addFilter(new EqualsAnyFilter('code', array_keys($itemsByCode)));
        $individualCodes = $this->individualCodeRepository->search($criteria, $context->getContext())->getEntities();

        foreach ($individualCodes as $individualCode) {
            $payload = $individualCode->getPayload();
            $restValue = $this->normalizePositiveRestValue($payload['restValue'] ?? null);
            $normalizedCode = mb_strtolower($individualCode->getCode());

            if ($restValue === null || !isset($itemsByCode[$normalizedCode])) {
                continue;
            }

            $remainingCurrencyValue = $restValue * $context->getCurrency()->getFactor();

            foreach ($itemsByCode[$normalizedCode] as $lineItem) {
                if ($lineItem->getPayloadValue('discountType') !== PromotionDiscountEntity::TYPE_ABSOLUTE) {
                    $promotionItems->remove($lineItem->getId());

                    continue;
                }

                $definition = $lineItem->getPriceDefinition();
                if (!$definition instanceof AbsolutePriceDefinition || $remainingCurrencyValue <= 0.0) {
                    $promotionItems->remove($lineItem->getId());

                    continue;
                }

                $discountValue = min(abs($definition->getPrice()), $remainingCurrencyValue);
                $remainingCurrencyValue -= $discountValue;

                $lineItem->setPriceDefinition(new AbsolutePriceDefinition(-$discountValue, $definition->getFilter()));
                $lineItem->setPayloadValue('limitedRedemptions', true);
                $lineItem->setPayloadValue('warexoRestValue', $restValue);
                $lineItem->setPayloadValue('warexoRestValueCurrency', $restValue * $context->getCurrency()->getFactor());
            }
        }
    }

    /**
     * @return array<string, list<LineItem>>
     */
    private function getIndividualPromotionItemsByCode(LineItemCollection $promotionItems): array
    {
        $itemsByCode = [];

        foreach ($promotionItems as $lineItem) {
            $code = $lineItem->getPayloadValue('code');

            if (
                !\is_string($code)
                || $code === ''
                || $lineItem->getPayloadValue('promotionCodeType') !== PromotionItemBuilder::PROMOTION_TYPE_INDIVIDUAL
            ) {
                continue;
            }

            $itemsByCode[mb_strtolower($code)][] = $lineItem;
        }

        return $itemsByCode;
    }

    private function normalizePositiveRestValue(mixed $value): ?float
    {
        if (!\is_int($value) && !\is_float($value) && !\is_string($value)) {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $restValue = (float) $value;

        return is_finite($restValue) && $restValue > 0.0 ? $restValue : null;
    }
}
