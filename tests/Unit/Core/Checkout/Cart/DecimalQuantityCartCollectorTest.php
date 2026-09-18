<?php declare(strict_types=1);

namespace Warexo\Tests\Unit\Core\Checkout\Cart;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\CalculatedTaxCollection;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Content\Product\SalesChannel\SalesChannelProductEntity;
use Warexo\Core\Checkout\Cart\DecimalQuantityCartCollector;

#[CoversClass(DecimalQuantityCartCollector::class)]
final class DecimalQuantityCartCollectorTest extends TestCase
{
    #[DataProvider('tierQuantities')]
    public function testResolvesEveryDecimalQuantityToItsCalculatedPriceRange(float $quantity, float $expectedUnitPrice): void
    {
        $product = new SalesChannelProductEntity();
        $product->setCalculatedPrice($this->price(0.99));
        $product->setCalculatedPrices(new PriceCollection([
            $this->price(0.99, 4),
            $this->price(0.85, 9),
            $this->price(0.69, 44),
            $this->price(0.59, 45),
        ]));

        $reflection = new ReflectionClass(DecimalQuantityCartCollector::class);
        $collector = $reflection->newInstanceWithoutConstructor();
        $selectedPrice = $reflection->getMethod('resolveLineItemPrice')->invoke($collector, $product, $quantity);

        static::assertInstanceOf(CalculatedPrice::class, $selectedPrice);
        static::assertSame($expectedUnitPrice, $selectedPrice->getUnitPrice());
    }

    /**
     * @return iterable<string, array{float, float}>
     */
    public static function tierQuantities(): iterable
    {
        yield 'base range start' => [1.0, 0.99];
        yield 'base range integer end' => [4.0, 0.99];
        yield 'base range decimal end' => [4.999, 0.99];
        yield 'first tier start' => [5.0, 0.85];
        yield 'inside first tier' => [6.0, 0.85];
        yield 'first tier decimal end' => [9.999, 0.85];
        yield 'second tier start' => [10.0, 0.69];
        yield 'inside second tier' => [23.456, 0.69];
        yield 'second tier decimal end' => [44.999, 0.69];
        yield 'open tier start' => [45.0, 0.59];
        yield 'inside open tier' => [49.999, 0.59];
    }

    private function price(float $unitPrice, int $quantity = 1): CalculatedPrice
    {
        return new CalculatedPrice(
            $unitPrice,
            $unitPrice * $quantity,
            new CalculatedTaxCollection(),
            new TaxRuleCollection(),
            $quantity
        );
    }
}
