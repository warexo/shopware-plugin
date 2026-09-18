<?php declare(strict_types=1);

namespace Warexo\Tests\Unit\Core\Content\Product\Quantity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityRequestTransformer;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityValidator;

#[CoversClass(DecimalQuantityRequestTransformer::class)]
#[CoversClass(DecimalQuantityValidator::class)]
final class DecimalQuantityRequestTransformerTest extends TestCase
{
    private DecimalQuantityRequestTransformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new DecimalQuantityRequestTransformer(
            new DecimalQuantityMapper(),
            new DecimalQuantityValidator()
        );
    }

    #[DataProvider('validQuantityProvider')]
    public function testTransformsValidQuantities(mixed $rawQuantity, float $decimalQuantity, int $coreQuantity): void
    {
        static::assertSame([
            'decimalQuantity' => $decimalQuantity,
            'coreQuantity' => $coreQuantity,
        ], $this->transformer->transform($rawQuantity));
    }

    /**
     * @return iterable<string, array{mixed, float, int}>
     */
    public static function validQuantityProvider(): iterable
    {
        yield 'float' => [1.25, 1.25, 1250];
        yield 'integer' => [2, 2.0, 2000];
        yield 'decimal point string' => [' 0.125 ', 0.125, 125];
        yield 'decimal comma string' => ['2,75', 2.75, 2750];
    }

    #[DataProvider('invalidQuantityProvider')]
    public function testRejectsInvalidQuantities(mixed $rawQuantity): void
    {
        static::assertNull($this->transformer->transform($rawQuantity));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidQuantityProvider(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => ['  '];
        yield 'non-numeric string' => ['one'];
        yield 'zero' => [0];
        yield 'negative' => [-1.5];
        yield 'more than three decimals' => [1.2345];
        yield 'array' => [['1.5']];
    }

    #[DataProvider('truthyProvider')]
    public function testRecognizesSupportedTruthyPayloadValues(mixed $value): void
    {
        static::assertTrue($this->transformer->isTruthy($value));
        static::assertTrue($this->transformer->isDecimalPayload(['warexoIsDecimalQuantity' => $value]));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function truthyProvider(): iterable
    {
        yield 'boolean' => [true];
        yield 'integer' => [1];
        yield 'float' => [1.0];
        yield 'numeric string' => ['1'];
        yield 'case-insensitive true' => [' TRUE '];
        yield 'yes' => ['yes'];
        yield 'on' => ['on'];
    }

    #[DataProvider('falsyProvider')]
    public function testRejectsUnsupportedTruthyPayloadValues(mixed $value): void
    {
        static::assertFalse($this->transformer->isTruthy($value));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function falsyProvider(): iterable
    {
        yield 'false' => [false];
        yield 'zero' => [0];
        yield 'other integer' => [2];
        yield 'false string' => ['false'];
        yield 'empty string' => [''];
        yield 'null' => [null];
        yield 'array' => [[]];
    }
}
