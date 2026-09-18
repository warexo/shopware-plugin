<?php declare(strict_types=1);

namespace Warexo\Tests\Unit\Core\Content\Product\Quantity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityIntervalCalculator;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;

#[CoversClass(DecimalQuantityIntervalCalculator::class)]
final class DecimalQuantityIntervalCalculatorTest extends TestCase
{
    #[DataProvider('intervalProvider')]
    public function testSnapsWithoutFloatingPointStepLoss(
        float $quantity,
        mixed $minPurchase,
        mixed $purchaseSteps,
        mixed $maxPurchase,
        float $expected
    ): void {
        $calculator = new DecimalQuantityIntervalCalculator(new DecimalQuantityMapper());

        static::assertSame($expected, $calculator->snap($quantity, $minPurchase, $purchaseSteps, $maxPurchase));
    }

    /**
     * @return iterable<string, array{float, mixed, mixed, mixed, float}>
     */
    public static function intervalProvider(): iterable
    {
        yield 'exact decimal maximum remains exact' => [1.2, 0.1, 0.1, 1.2, 1.2];
        yield 'quantity above maximum is clamped' => [2.2, 0.1, 0.1, 1.2, 1.2];
        yield 'maximum is snapped down to valid interval' => [2.0, 0.5, 0.5, 1.2, 1.0];
        yield 'localized numeric configuration is supported' => [1.2, '0,1', '0,1', '1,2', 1.2];
    }
}
