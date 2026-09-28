<?php declare(strict_types=1);

namespace Warexo\Tests\Unit\Subscriber;

use PHPUnit\Framework\Attributes\CoversClass;
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
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Warexo\Subscriber\RestValuePromotionCodeRedeemer;

#[CoversClass(RestValuePromotionCodeRedeemer::class)]
final class RestValuePromotionCodeRedeemerTest extends TestCase
{
    private const CODE_ID = 'c0dec0dec0dec0dec0dec0dec0de9204';
    private const ORDER_ID = 'c0dec0dec0dec0dec0dec0dec0de9210';

    public function testCurrentRestValueIsAcceptedBeforeCoreRedeemsTheCode(): void
    {
        $context = Context::createDefaultContext();
        $code = $this->code(5.0);
        $subscriber = new RestValuePromotionCodeRedeemer($this->repository($context, $code));

        $subscriber->validateRestValueRedemptions($this->event($context, 5.0));

        static::assertSame(['restValue' => 5.0], $code->getPayload());
    }

    public function testReducedRestValueIsPersistedAfterCoreRedeemsTheCode(): void
    {
        $context = Context::createDefaultContext();
        $code = $this->code(100.0);
        $subscriber = new RestValuePromotionCodeRedeemer($this->repository($context, $code));

        $this->writeOrder($subscriber, $code, $this->event($context, 100.0));

        static::assertSame(80.01, $code->getPayload()['restValue']);
        static::assertSame(self::ORDER_ID, $code->getPayload()['orderId']);
        static::assertSame('Test Customer', $code->getPayload()['customerName']);
    }

    public function testChangedRestValueRejectsAStaleCart(): void
    {
        $context = Context::createDefaultContext();
        $code = $this->code(3.0);
        $repository = $this->repository($context, $code);
        $repository->expects(static::never())->method('update');

        $this->expectException(PromotionException::class);

        (new RestValuePromotionCodeRedeemer($repository))->validateRestValueRedemptions($this->event($context, 5.0));
    }

    public function testSerializedArrayPriceIsSupported(): void
    {
        $context = Context::createDefaultContext();
        $code = $this->code(100.0);
        $subscriber = new RestValuePromotionCodeRedeemer($this->repository($context, $code));

        $this->writeOrder($subscriber, $code, $this->event($context, 100.0, false));

        static::assertSame(80.01, $code->getPayload()['restValue']);
    }

    public function testTwoPartialRedemptionsLeaveFourEuros(): void
    {
        $context = Context::createDefaultContext();
        $code = $this->code(20.0, null);
        $subscriber = new RestValuePromotionCodeRedeemer($this->repository($context, $code));

        $this->writeOrder($subscriber, $code, $this->event($context, 20.0, redeemedValue: 8.0, orderId: 'first-order'));
        static::assertSame(12.0, $code->getPayload()['restValue']);

        $this->writeOrder($subscriber, $code, $this->event($context, 12.0, redeemedValue: 8.0));
        static::assertSame(4.0, $code->getPayload()['restValue']);
    }

    public function testSameOrderWithRecalculatedSnapshotDoesNotRedeemAgain(): void
    {
        $context = Context::createDefaultContext();
        $code = $this->code(4.0, self::ORDER_ID);
        $subscriber = new RestValuePromotionCodeRedeemer($this->repository($context, $code));

        $this->writeOrder($subscriber, $code, $this->event($context, 4.0, redeemedValue: 8.0));

        static::assertSame(4.0, $code->getPayload()['restValue']);
    }

    public function testSameOrderWithOriginalSnapshotKeepsBalance(): void
    {
        $context = Context::createDefaultContext();
        $code = $this->code(4.0, self::ORDER_ID);
        $subscriber = new RestValuePromotionCodeRedeemer($this->repository($context, $code));

        $this->writeOrder($subscriber, $code, $this->event($context, 12.0, redeemedValue: 8.0));

        static::assertSame(4.0, $code->getPayload()['restValue']);
    }

    public function testSameOrderWithoutPriceKeepsBalance(): void
    {
        $context = Context::createDefaultContext();
        $code = $this->code(4.0, self::ORDER_ID);
        $subscriber = new RestValuePromotionCodeRedeemer($this->repository($context, $code));

        $this->writeOrder($subscriber, $code, $this->event($context, 12.0, withPrice: false));

        static::assertSame(4.0, $code->getPayload()['restValue']);
    }

    public function testNewOrderWithoutPriceIsRejected(): void
    {
        $context = Context::createDefaultContext();
        $code = $this->code(12.0);
        $repository = $this->repository($context, $code);
        $repository->expects(static::never())->method('update');

        $this->expectException(PromotionException::class);

        (new RestValuePromotionCodeRedeemer($repository))->validateRestValueRedemptions(
            $this->event($context, 12.0, withPrice: false)
        );
    }

    public function testRepeatedWriteKeepsExhaustedBalance(): void
    {
        $context = Context::createDefaultContext();
        $code = $this->code(20.0, null);
        $subscriber = new RestValuePromotionCodeRedeemer($this->repository($context, $code));

        $this->writeOrder($subscriber, $code, $this->event($context, 20.0, redeemedValue: 20.0));
        static::assertSame(0.0, $code->getPayload()['restValue']);

        $this->writeOrder($subscriber, $code, $this->event($context, 0.0, redeemedValue: 20.0));
        static::assertSame(0.0, $code->getPayload()['restValue']);
    }

    public function testForeignCurrencyRedemptionIsConvertedToBaseCurrency(): void
    {
        $context = Context::createDefaultContext();
        $code = $this->code(20.0, null);
        $subscriber = new RestValuePromotionCodeRedeemer($this->repository($context, $code));

        $this->writeOrder($subscriber, $code, $this->event($context, 20.0, redeemedValue: 16.0, restValueCurrency: 40.0));

        static::assertSame(12.0, $code->getPayload()['restValue']);
    }

    public function testPersistenceWithoutValidationDoesNothing(): void
    {
        $context = Context::createDefaultContext();
        $repository = $this->createMock(EntityRepository::class);
        $repository->expects(static::never())->method('search');
        $repository->expects(static::never())->method('update');

        (new RestValuePromotionCodeRedeemer($repository))->persistRestValueRedemptions($this->event($context, 4.0));
    }

    private function writeOrder(RestValuePromotionCodeRedeemer $subscriber, PromotionIndividualCodeEntity $code, EntityWrittenEvent $event): void
    {
        $subscriber->validateRestValueRedemptions($event);
        // Core replaces the code payload between the two subscriber callbacks.
        $code->setRedeemed($event->getWriteResults()[0]->getPayload()['orderId'], 'customer-id', 'Test Customer');
        $subscriber->persistRestValueRedemptions($event);
    }

    private function event(
        Context $context,
        float $restValue,
        bool $objectPrice = true,
        float $redeemedValue = 19.99,
        string $orderId = self::ORDER_ID,
        bool $withPrice = true,
        ?float $restValueCurrency = null
    ): EntityWrittenEvent {
        $payload = [
            'type' => 'promotion',
            'orderId' => $orderId,
            'payload' => [
                'code' => 'WAREXO-REST-VALUE-TEST',
                'warexoRestValue' => $restValue,
                'warexoRestValueCurrency' => $restValueCurrency ?? $restValue,
            ],
        ];
        if ($withPrice) {
            $payload['price'] = $objectPrice
                ? new CalculatedPrice(-$redeemedValue, -$redeemedValue, new CalculatedTaxCollection(), new TaxRuleCollection())
                : ['totalPrice' => -$redeemedValue];
        }
        $writeResult = new EntityWriteResult(
            'c0dec0dec0dec0dec0dec0dec0de9220',
            $payload,
            'order_line_item',
            EntityWriteResult::OPERATION_INSERT
        );

        return new EntityWrittenEvent('order_line_item', [$writeResult], $context);
    }

    private function code(float $restValue, ?string $orderId = 'previous-order'): PromotionIndividualCodeEntity
    {
        $payload = ['restValue' => $restValue];
        if ($orderId !== null) {
            $payload['orderId'] = $orderId;
        }

        return (new PromotionIndividualCodeEntity())->assign([
            'id' => self::CODE_ID,
            'promotionId' => 'c0dec0dec0dec0dec0dec0dec0de9201',
            'code' => 'WAREXO-REST-VALUE-TEST',
            'payload' => $payload,
        ]);
    }

    private function repository(Context $context, PromotionIndividualCodeEntity $code): EntityRepository
    {
        $repository = $this->createMock(EntityRepository::class);
        $repository->method('search')->willReturn(new EntitySearchResult(
            'promotion_individual_code',
            1,
            new PromotionIndividualCodeCollection([$code]),
            null,
            new Criteria(),
            $context
        ));
        $repository->method('update')->willReturnCallback(static function (array $data, Context $context) use ($code) {
            foreach ($data as $row) {
                $code->setPayload($row['payload']);
            }

            return EntityWrittenContainerEvent::createWithWrittenEvents([], $context, []);
        });

        return $repository;
    }
}
