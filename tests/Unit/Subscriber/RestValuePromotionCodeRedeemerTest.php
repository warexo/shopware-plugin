<?php declare(strict_types=1);

namespace Warexo\Tests\Unit\Subscriber;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Promotion\Aggregate\PromotionIndividualCode\PromotionIndividualCodeCollection;
use Shopware\Core\Checkout\Promotion\Aggregate\PromotionIndividualCode\PromotionIndividualCodeEntity;
use Shopware\Core\Checkout\Promotion\PromotionException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Warexo\Subscriber\RestValuePromotionCodeRedeemer;

class RestValuePromotionCodeRedeemerTest extends TestCase
{
    private const CODE_ID = 'c0dec0dec0dec0dec0dec0dec0de9204';
    private const ORDER_ID = 'c0dec0dec0dec0dec0dec0dec0de9210';

    public function testCurrentRestValueIsAcceptedBeforeCoreRedeemsTheCode(): void
    {
        $context = Context::createDefaultContext();
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('search')->willReturn($this->searchResult($context, 5.0));
        $repository->expects(static::once())->method('update')->with(
            [[
                'id' => self::CODE_ID,
                'payload' => [
                    'restValue' => 5.0,
                ],
            ]],
            $context
        );

        (new RestValuePromotionCodeRedeemer($repository))->validateRestValueRedemptions($this->event($context, 5.0));
    }

    public function testReducedRestValueIsPersistedAfterCoreRedeemsTheCode(): void
    {
        $context = Context::createDefaultContext();
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('search')->willReturn($this->searchResult($context, 100.0, self::ORDER_ID));
        $repository->expects(static::once())->method('update')->with(
            [[
                'id' => self::CODE_ID,
                'payload' => [
                    'orderId' => self::ORDER_ID,
                    'restValue' => 80.01,
                ],
            ]],
            $context
        );

        (new RestValuePromotionCodeRedeemer($repository))->persistRestValueRedemptions($this->event($context, 100.0));
    }

    public function testChangedRestValueRejectsAStaleCart(): void
    {
        $context = Context::createDefaultContext();
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('search')->willReturn($this->searchResult($context, 3.0));
        $repository->expects(static::never())->method('update');

        $this->expectException(PromotionException::class);

        (new RestValuePromotionCodeRedeemer($repository))->validateRestValueRedemptions($this->event($context, 5.0));
    }

    public function testSerializedArrayPriceIsSupported(): void
    {
        $context = Context::createDefaultContext();
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('search')->willReturn($this->searchResult($context, 100.0, self::ORDER_ID));
        $repository->expects(static::once())->method('update')->with(
            [[
                'id' => self::CODE_ID,
                'payload' => [
                    'orderId' => self::ORDER_ID,
                    'restValue' => 80.01,
                ],
            ]],
            $context
        );

        (new RestValuePromotionCodeRedeemer($repository))->persistRestValueRedemptions(
            $this->event($context, 100.0, false)
        );
    }

    private function event(Context $context, float $restValue, bool $objectPrice = true): EntityWrittenEvent
    {
        $writeResult = new EntityWriteResult(
            'c0dec0dec0dec0dec0dec0dec0de9220',
            [
                'type' => 'promotion',
                'orderId' => self::ORDER_ID,
                'payload' => [
                    'code' => 'WAREXO-REST-VALUE-TEST',
                    'warexoRestValue' => $restValue,
                    'warexoRestValueCurrency' => $restValue,
                ],
                'price' => $objectPrice
                    ? new CalculatedPrice(
                        -19.99,
                        -19.99,
                        new CalculatedTaxCollection(),
                        new TaxRuleCollection()
                    )
                    : ['totalPrice' => -19.99],
            ],
            'order_line_item',
            EntityWriteResult::OPERATION_INSERT
        );

        return new EntityWrittenEvent('order_line_item', [$writeResult], $context);
    }

    /**
     * @return EntitySearchResult<PromotionIndividualCodeCollection>
     */
    private function searchResult(Context $context, float $restValue, string $orderId = 'previous-order'): EntitySearchResult
    {
        $code = (new PromotionIndividualCodeEntity())->assign([
            'id' => self::CODE_ID,
            'promotionId' => 'c0dec0dec0dec0dec0dec0dec0de9201',
            'code' => 'WAREXO-REST-VALUE-TEST',
            'payload' => [
                'orderId' => $orderId,
                'restValue' => $restValue,
            ],
        ]);
        $codes = new PromotionIndividualCodeCollection([$code]);

        return new EntitySearchResult(
            'promotion_individual_code',
            $codes->count(),
            $codes,
            null,
            new Criteria(),
            $context
        );
    }
}
