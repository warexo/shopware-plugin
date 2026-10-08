<?php declare(strict_types=1);

namespace Warexo\Tests\Unit\Core\Content\Product\Stock;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Events\InvalidateProductCache;
use Shopware\Core\Content\Product\Stock\AbstractStockStorage;
use Shopware\Core\Content\Product\Stock\StockAlteration;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityFeatureDecider;
use Warexo\Core\Content\Product\Stock\StockStorage;

final class StockStorageTest extends TestCase
{
    #[DataProvider('decimalModes')]
    public function testStockChangeInvalidatesImmediatelyAfterBothStocksAreUpdated(bool $decimal): void
    {
        $productId = Uuid::randomHex();
        $changes = [
            new StockAlteration(Uuid::randomHex(), $productId, 0, 1000),
            new StockAlteration(Uuid::randomHex(), $productId, 0, 500),
        ];
        $context = Context::createDefaultContext();
        $order = [];
        $inner = $this->createMock(AbstractStockStorage::class);
        $inner->expects(static::once())->method('alter')->with($changes, $context)
            ->willReturnCallback(static function () use (&$order): void {
                $order[] = 'core';
            });
        $connection = $this->createMock(Connection::class);
        $connection->method('transactional')->willReturnCallback(static fn (callable $callback) => $callback());
        $connection->expects($decimal ? static::once() : static::never())->method('executeStatement')
            ->willReturnCallback(static function () use (&$order): int {
                $order[] = 'decimal';

                return 1;
            });
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(InvalidateProductCache::class, static function (InvalidateProductCache $event) use (&$order, $productId): void {
            static::assertSame([$productId], $event->getIds());
            static::assertTrue($event->force);
            $order[] = 'cache';
        });

        (new StockStorage($inner, $connection, $this->featureDecider($decimal), $dispatcher))->alter($changes, $context);

        static::assertSame($decimal ? ['core', 'decimal', 'cache'] : ['core', 'cache'], $order);
    }

    public function testFailedStockChangeDoesNotInvalidateOrMirrorStock(): void
    {
        $inner = $this->createMock(AbstractStockStorage::class);
        $inner->method('alter')->willThrowException(new \RuntimeException('Stock write failed'));
        $connection = $this->createMock(Connection::class);
        $connection->method('transactional')->willReturnCallback(static fn (callable $callback) => $callback());
        $connection->expects(static::never())->method('executeStatement');
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(InvalidateProductCache::class, static function (): void {
            static::fail('Failed stock changes must not invalidate product pages.');
        });
        $storage = new StockStorage($inner, $connection, $this->featureDecider(true), $dispatcher);

        $this->expectExceptionMessage('Stock write failed');
        $storage->alter([new StockAlteration(Uuid::randomHex(), Uuid::randomHex(), 0, 1)], Context::createDefaultContext());
    }

    public function testDraftStockChangeDoesNotWriteDecimalStockOrInvalidateLivePages(): void
    {
        $inner = $this->createMock(AbstractStockStorage::class);
        $inner->expects(static::once())->method('alter');
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::never())->method('transactional');
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(InvalidateProductCache::class, static function (): void {
            static::fail('Draft writes must not invalidate live product pages.');
        });

        (new StockStorage($inner, $connection, $this->featureDecider(true), $dispatcher))->alter(
            [new StockAlteration(Uuid::randomHex(), Uuid::randomHex(), 0, 1)],
            Context::createDefaultContext()->createWithVersionId(Uuid::randomHex())
        );
    }

    public static function decimalModes(): iterable
    {
        yield 'whole quantities' => [false];
        yield 'decimal quantities' => [true];
    }

    private function featureDecider(bool $enabled): DecimalQuantityFeatureDecider
    {
        $config = $this->createMock(SystemConfigService::class);
        $config->method('get')->willReturn($enabled);

        return new DecimalQuantityFeatureDecider($config);
    }
}
