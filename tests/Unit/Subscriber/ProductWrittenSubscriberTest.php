<?php declare(strict_types=1);

namespace Warexo\Tests\Unit\Subscriber;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Events\InvalidateProductCache;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityWriteResult;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityFeatureDecider;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityValidator;
use Warexo\Subscriber\ProductWrittenSubscriber;

final class ProductWrittenSubscriberTest extends TestCase
{
    #[DataProvider('decimalModes')]
    public function testSyncStockUpdateInvalidatesImmediatelyWithoutIndexer(bool $decimal): void
    {
        $productId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $context->addState('disable-indexing');
        $connection = $this->createMock(Connection::class);
        $connection->method('fetchAllKeyValue')->willReturn([$productId => 0]);
        $connection->method('fetchAllAssociative')->willReturn([
            ['product_id' => $productId, 'stock' => '0.000', 'min_purchase' => '0.500', 'max_purchase' => null, 'purchase_steps' => '0.500'],
        ]);
        $config = $this->createMock(SystemConfigService::class);
        $config->method('get')->willReturn($decimal);
        $events = [];
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(InvalidateProductCache::class, static function (InvalidateProductCache $event) use (&$events): void {
            $events[] = $event;
        });
        $subscriber = new ProductWrittenSubscriber(
            $connection,
            $dispatcher,
            new DecimalQuantityFeatureDecider($config),
            new DecimalQuantityMapper(),
            new DecimalQuantityValidator()
        );

        $subscriber->productWritten(new EntityWrittenEvent('product', [
            new EntityWriteResult($productId, ['stock' => 0], 'product', EntityWriteResult::OPERATION_UPDATE),
        ], $context));

        static::assertCount(1, $events);
        static::assertSame([$productId], $events[0]->getIds());
        static::assertTrue($events[0]->force);
    }

    public static function decimalModes(): iterable
    {
        yield 'whole quantities' => [false];
        yield 'decimal quantities' => [true];
    }
}
