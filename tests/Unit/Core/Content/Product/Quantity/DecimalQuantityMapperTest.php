<?php declare(strict_types=1);

namespace Warexo\Tests\Unit\Core\Content\Product\Quantity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;

#[CoversClass(DecimalQuantityMapper::class)]
final class DecimalQuantityMapperTest extends TestCase
{
    #[DataProvider('quantityProvider')]
    public function testMapsDecimalAndCoreQuantities(float $decimalQuantity, int $coreQuantity): void
    {
        $mapper = new DecimalQuantityMapper();

        static::assertSame($coreQuantity, $mapper->toCoreQuantity($decimalQuantity));
        static::assertSame($decimalQuantity, $mapper->fromCoreQuantity($coreQuantity));
    }

    /**
     * @return iterable<string, array{float, int}>
     */
    public static function quantityProvider(): iterable
    {
        yield 'whole unit' => [1.0, 1000];
        yield 'fractional unit' => [1.234, 1234];
        yield 'smallest supported fraction' => [0.001, 1];
        yield 'rounds beyond supported scale' => [1.235, 1235];
    }

    #[DataProvider('unitPriceProvider')]
    public function testMapsBusinessAndCoreUnitPrices(float $businessUnitPrice, float $coreUnitPrice): void
    {
        $mapper = new DecimalQuantityMapper();

        static::assertSame($coreUnitPrice, $mapper->toCoreUnitPrice($businessUnitPrice));
        static::assertSame($businessUnitPrice, $mapper->fromCoreUnitPrice($coreUnitPrice));
    }

    /**
     * @return iterable<string, array{float, float}>
     */
    public static function unitPriceProvider(): iterable
    {
        yield 'regular price' => [19.99, 0.01999];
        yield 'fractional price' => [12.345, 0.012345];
        yield 'zero price' => [0.0, 0.0];
    }
}
