<?php declare(strict_types=1);

namespace Warexo\Tests\Unit\Core\Checkout\Cart;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemQuantitySplitter;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTax;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Warexo\Core\Checkout\Cart\DecimalLineItemQuantitySplitter;

#[CoversClass(DecimalLineItemQuantitySplitter::class)]
final class DecimalLineItemQuantitySplitterTest extends TestCase
{
    public function testSplitsDecimalQuantityAndTaxesProportionally(): void
    {
        $decorated = $this->createMock(LineItemQuantitySplitter::class);
        $decorated->expects(static::never())->method('split');
        $splitter = new DecimalLineItemQuantitySplitter($decorated, $this->withoutConstructor(QuantityPriceCalculator::class));
        $context = $this->withoutConstructor(SalesChannelContext::class);

        $lineItem = new LineItem('line-item', LineItem::PRODUCT_LINE_ITEM_TYPE, 'product-id', 1500);
        $lineItem->setStackable(true);
        $lineItem->setPayloadValue('warexoIsDecimalQuantity', true);
        $lineItem->setPrice(new CalculatedPrice(
            0.01999,
            29.985,
            new CalculatedTaxCollection([new CalculatedTax(4.7875, 19.0, 29.985)]),
            new TaxRuleCollection([new TaxRule(19.0)]),
            1500
        ));

        $split = $splitter->split($lineItem, 500, $context);

        static::assertNotSame($lineItem, $split);
        static::assertSame(1500, $lineItem->getQuantity());
        static::assertSame(500, $split->getQuantity());
        static::assertInstanceOf(CalculatedPrice::class, $split->getPrice());
        static::assertEqualsWithDelta(9.995, $split->getPrice()->getTotalPrice(), 0.000001);
        static::assertEqualsWithDelta(1.5958333333, $split->getPrice()->getCalculatedTaxes()->first()?->getTax(), 0.000001);
        static::assertEqualsWithDelta(9.995, $split->getPrice()->getCalculatedTaxes()->first()?->getPrice(), 0.000001);
    }

    public function testReturnsACloneWhenTheFullDecimalQuantityIsRequested(): void
    {
        $decorated = $this->createMock(LineItemQuantitySplitter::class);
        $decorated->expects(static::never())->method('split');
        $splitter = new DecimalLineItemQuantitySplitter($decorated, $this->withoutConstructor(QuantityPriceCalculator::class));
        $context = $this->withoutConstructor(SalesChannelContext::class);
        $lineItem = new LineItem('line-item', LineItem::PRODUCT_LINE_ITEM_TYPE, 'product-id', 1250);
        $lineItem->setPayloadValue('warexoIsDecimalQuantity', 'yes');

        $split = $splitter->split($lineItem, 1250, $context);

        static::assertNotSame($lineItem, $split);
        static::assertEquals($lineItem, $split);
    }

    public function testDelegatesNonDecimalLineItemsToShopware(): void
    {
        $context = $this->withoutConstructor(SalesChannelContext::class);
        $lineItem = new LineItem('line-item', LineItem::PRODUCT_LINE_ITEM_TYPE, 'product-id');
        $expected = new LineItem('split-line-item', LineItem::PRODUCT_LINE_ITEM_TYPE, 'product-id');
        $decorated = $this->createMock(LineItemQuantitySplitter::class);
        $decorated->expects(static::once())
            ->method('split')
            ->with($lineItem, 1, $context)
            ->willReturn($expected);
        $splitter = new DecimalLineItemQuantitySplitter($decorated, $this->withoutConstructor(QuantityPriceCalculator::class));

        static::assertSame($expected, $splitter->split($lineItem, 1, $context));
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    private function withoutConstructor(string $class): object
    {
        return (new ReflectionClass($class))->newInstanceWithoutConstructor();
    }
}
