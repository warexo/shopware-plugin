<?php declare(strict_types=1);

namespace Warexo\Tests\Unit\Core\Content\Product\Stock;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Product\Stock\AbstractStockStorage;
use Shopware\Core\Content\Product\Stock\OrderStockSubscriber;
use Shopware\Core\Content\Product\Stock\StockAlteration;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWriteEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\InsertCommand;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineState\StateMachineStateEntity;
use Shopware\Core\System\StateMachine\Event\StateMachineTransitionEvent;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class OrderStockSubscriberTest extends TestCase
{
    #[DataProvider('stockManagementModes')]
    public function testSuccessfulOrderDeductsStockExactlyOnce(bool $enabled): void
    {
        $id = Uuid::fromHexToBytes(Uuid::randomHex());
        $productId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::exactly(2))->method('fetchAllAssociativeIndexed')->willReturnOnConsecutiveCalls(
            [],
            [$id => ['id' => $id, 'referenced_id' => $productId, 'quantity' => 2]]
        );
        $storage = $this->createMock(AbstractStockStorage::class);
        $storage->expects(static::once())->method('alter')->with(
            [new StockAlteration($id, $productId, 0, 2)],
            $context
        );
        $event = $this->orderWriteEvent($id, $context);

        $this->dispatcher($enabled, $connection, $storage)->dispatch($event);
        $event->success();
    }

    #[DataProvider('stockManagementModes')]
    public function testDraftOrderDoesNotAffectLiveStock(bool $enabled): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::never())->method('fetchAllAssociativeIndexed');
        $storage = $this->createMock(AbstractStockStorage::class);
        $storage->expects(static::never())->method('alter');
        $event = $this->orderWriteEvent(
            Uuid::fromHexToBytes(Uuid::randomHex()),
            Context::createDefaultContext()->createWithVersionId(Uuid::randomHex())
        );

        $this->dispatcher($enabled, $connection, $storage)->dispatch($event);
        $event->success();
    }

    #[DataProvider('stockManagementModes')]
    public function testFailedOrderDoesNotDeductStock(bool $enabled): void
    {
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())->method('fetchAllAssociativeIndexed')->willReturn([]);
        $storage = $this->createMock(AbstractStockStorage::class);
        $storage->expects(static::never())->method('alter');
        $event = $this->orderWriteEvent(Uuid::fromHexToBytes(Uuid::randomHex()), Context::createDefaultContext());

        $this->dispatcher($enabled, $connection, $storage)->dispatch($event);
        $event->error();
    }

    #[DataProvider('transitions')]
    public function testCancellationAndReopeningChangeStockExactlyOnce(bool $enabled, string $from, string $to, int $before, int $after): void
    {
        $id = Uuid::fromHexToBytes(Uuid::randomHex());
        $productId = Uuid::randomHex();
        $context = Context::createDefaultContext();
        $connection = $this->createMock(Connection::class);
        $connection->expects(static::once())->method('fetchAllAssociative')
            ->willReturn([['id' => $id, 'product_id' => $productId, 'quantity' => '2']]);
        $storage = $this->createMock(AbstractStockStorage::class);
        $storage->expects(static::once())->method('alter')->with(
            [new StockAlteration($id, $productId, $before, $after)],
            $context
        );
        $fromState = new StateMachineStateEntity();
        $fromState->setTechnicalName($from);
        $toState = new StateMachineStateEntity();
        $toState->setTechnicalName($to);

        $this->dispatcher($enabled, $connection, $storage)->dispatch(
            new StateMachineTransitionEvent('order', Uuid::randomHex(), $fromState, $toState, $context)
        );
    }

    public static function stockManagementModes(): iterable
    {
        yield 'core stock management disabled' => [false];
        yield 'core stock management enabled' => [true];
    }

    public static function transitions(): iterable
    {
        foreach ([false, true] as $enabled) {
            yield 'cancel with stock management ' . (int) $enabled => [$enabled, 'open', 'cancelled', 2, 0];
            yield 'reopen with stock management ' . (int) $enabled => [$enabled, 'cancelled', 'open', 0, 2];
        }
    }

    private function orderWriteEvent(string $id, Context $context): EntityWriteEvent
    {
        $command = $this->getMockBuilder(InsertCommand::class)->disableOriginalConstructor()->getMock();
        $command->method('getEntityName')->willReturn('order_line_item');
        $command->method('getPrimaryKey')->willReturn(['id' => $id]);
        $command->method('getDecodedPrimaryKey')->willReturn(['id' => Uuid::fromBytesToHex($id)]);

        return EntityWriteEvent::create(WriteContext::createFromContext($context), [$command]);
    }

    private function dispatcher(bool $enabled, Connection $connection, AbstractStockStorage $storage): EventDispatcher
    {
        $container = new ContainerBuilder();
        $container->setParameter('shopware.stock.enable_stock_management', $enabled);
        $loader = new XmlFileLoader($container, new FileLocator(dirname(__DIR__, 6) . '/src/Resources/config'));
        $loader->load('services.xml');
        $container->set(Connection::class, $connection);
        $container->set('Shopware\Core\Content\Product\Stock\StockStorage', $storage);
        $container->register(OrderStockSubscriber::class, OrderStockSubscriber::class)
            ->setArguments([new Reference(Connection::class), new Reference('Shopware\Core\Content\Product\Stock\StockStorage'), '%shopware.stock.enable_stock_management%'])
            ->addTag('kernel.event_subscriber');

        $dispatcher = new EventDispatcher();
        foreach ($container->findTaggedServiceIds('kernel.event_subscriber') as $id => $tags) {
            if ($id === OrderStockSubscriber::class || $id === 'warexo.order_stock_subscriber') {
                $dispatcher->addSubscriber($container->get($id));
            }
        }

        return $dispatcher;
    }
}
