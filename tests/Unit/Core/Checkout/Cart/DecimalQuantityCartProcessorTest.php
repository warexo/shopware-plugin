<?php declare(strict_types=1);

namespace Warexo\Tests\Unit\Core\Checkout\Cart;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\AmountCalculator;
use Shopware\Core\Checkout\Cart\Price\CashRounding;
use Shopware\Core\Checkout\Cart\Price\GrossPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\NetPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\PriceCollection;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\Tax\PercentageTaxRuleBuilder;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRule;
use Shopware\Core\Checkout\Cart\Tax\Struct\TaxRuleCollection;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use Shopware\Core\Content\Product\Cart\ProductStockReachedError;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Warexo\Core\Checkout\Cart\DecimalQuantityCartData;
use Warexo\Core\Checkout\Cart\DecimalQuantityCartProcessor;
use Warexo\Core\Checkout\Cart\Error\DecimalProductStockReachedError;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;

#[CoversClass(DecimalQuantityCartProcessor::class)]
#[CoversClass(DecimalProductStockReachedError::class)]
final class DecimalQuantityCartProcessorTest extends TestCase
{
    public function testPreservesCoreStockAdjustmentInsteadOfRestoringCollectedQuantity(): void
    {
        $productId = 'c0dec0dec0dec0dec0dec0dec0de4003';
        $lineItem = new LineItem($productId, LineItem::PRODUCT_LINE_ITEM_TYPE, $productId, 1200);
        $lineItem->setStackable(true);
        $cart = (new Cart('calculated'))->add($lineItem);
        $cart->addErrors(new ProductStockReachedError($productId, 'Decimal product', 1200));
        $data = new CartDataCollection([
            DecimalQuantityCartData::DATA_KEY => [
                $productId => [
                    'coreQuantity' => 2200,
                    'decimalQuantity' => 2.2,
                    'minPurchase' => 0.1,
                    'maxPurchase' => 1.2,
                    'purchaseSteps' => 0.1,
                ],
            ],
        ]);
        $processor = new DecimalQuantityCartProcessor(
            $this->withoutConstructor(QuantityPriceCalculator::class),
            new DecimalQuantityMapper(),
            $this->withoutConstructor(TaxCalculator::class),
            new CashRounding()
        );

        $processor->process(
            $data,
            new Cart('original'),
            $cart,
            $this->withoutConstructor(SalesChannelContext::class),
            new CartBehavior()
        );

        static::assertSame(1200, $lineItem->getQuantity());
        static::assertSame(1.2, $lineItem->getPayloadValue('warexoDecimalQuantity'));
        static::assertSame(1200, $lineItem->getPayloadValue('warexoCoreQuantity'));
        $error = $cart->getErrors()->get('product-stock-reached' . $productId);
        static::assertInstanceOf(DecimalProductStockReachedError::class, $error);
        static::assertSame(['name' => 'Decimal product', 'quantity' => 1.2], $error->getParameters());
    }

    #[DataProvider('roundingCases')]
    public function testCartTotalSumsRoundedDecimalLineItemTotals(
        string $taxState,
        CashRoundingConfig $rounding,
        float $unitPrice,
        float $expectedLineTotal
    ): void
    {
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getTaxState')->willReturn($taxState);
        $context->method('getTaxCalculationType')->willReturn(SalesChannelDefinition::CALCULATION_TYPE_HORIZONTAL);
        $context->method('getItemRounding')->willReturn($rounding);
        $context->method('getTotalRounding')->willReturn($rounding);

        $taxCalculator = new TaxCalculator();
        $calculator = new QuantityPriceCalculator(
            new GrossPriceCalculator($taxCalculator, new CashRounding()),
            new NetPriceCalculator($taxCalculator, new CashRounding())
        );
        $processor = new DecimalQuantityCartProcessor(
            $calculator,
            new DecimalQuantityMapper(),
            $taxCalculator,
            new CashRounding()
        );
        $cart = new Cart('calculated');
        $lineItemData = [];

        foreach (['first', 'second'] as $id) {
            $definition = new QuantityPriceDefinition(
                $unitPrice / DecimalQuantityMapper::FACTOR,
                new TaxRuleCollection([new TaxRule(19)]),
                500
            );
            $lineItem = new LineItem($id, LineItem::PRODUCT_LINE_ITEM_TYPE, $id, 500);
            $lineItem->setStackable(true);
            $lineItem->setPriceDefinition($definition);
            $lineItem->setPrice($calculator->calculate($definition, $context));
            $cart->add($lineItem);
            $lineItemData[$id] = [
                'coreQuantity' => 500,
                'decimalQuantity' => 0.5,
                'businessUnitPrice' => $unitPrice,
            ];
        }

        $data = new CartDataCollection([DecimalQuantityCartData::DATA_KEY => $lineItemData]);
        for ($pass = 0; $pass < 2; ++$pass) {
            $processor->process($data, new Cart('original'), $cart, $context, new CartBehavior());
        }

        $prices = new PriceCollection();
        foreach ($cart->getLineItems() as $lineItem) {
            $price = $lineItem->getPrice();
            static::assertNotNull($price);
            static::assertSame($expectedLineTotal, $price->getTotalPrice());
            static::assertSame(500, $lineItem->getQuantity());
            static::assertSame(0.5, $lineItem->getPayloadValue('warexoDecimalQuantity'));
            if ($taxState === CartPrice::TAX_STATE_FREE) {
                static::assertCount(0, $price->getCalculatedTaxes());
            } else {
                static::assertSame($expectedLineTotal, $price->getCalculatedTaxes()->first()?->getPrice());
            }
            $prices->add($price);
        }

        $amount = (new AmountCalculator(new CashRounding(), new PercentageTaxRuleBuilder(), $taxCalculator))
            ->calculate($prices, new PriceCollection(), $context);

        static::assertSame(2 * $expectedLineTotal, $amount->getPositionPrice());
        if ($taxState !== CartPrice::TAX_STATE_NET) {
            static::assertSame(2 * $expectedLineTotal, $amount->getTotalPrice());
        }
    }

    /**
     * @return iterable<string, array{string, CashRoundingConfig, float, float}>
     */
    public static function roundingCases(): iterable
    {
        yield 'gross' => [CartPrice::TAX_STATE_GROSS, new CashRoundingConfig(2, 0.01, true), 24.99, 12.5];
        yield 'net' => [CartPrice::TAX_STATE_NET, new CashRoundingConfig(2, 0.01, true), 24.99, 12.5];
        yield 'tax-free' => [CartPrice::TAX_STATE_FREE, new CashRoundingConfig(2, 0.01, true), 24.99, 12.5];
        yield 'three currency decimals' => [CartPrice::TAX_STATE_GROSS, new CashRoundingConfig(3, 0.001, true), 24.99, 12.495];
        yield 'gross cash interval' => [CartPrice::TAX_STATE_GROSS, new CashRoundingConfig(2, 0.05, false), 24.93, 12.45];
        yield 'net without cash rounding' => [CartPrice::TAX_STATE_NET, new CashRoundingConfig(2, 0.05, false), 24.93, 12.47];
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
