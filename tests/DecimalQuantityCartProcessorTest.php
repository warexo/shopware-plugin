<?php declare(strict_types=1);

namespace Warexo\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
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
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\CashRoundingConfig;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use Warexo\Core\Checkout\Cart\DecimalQuantityCartData;
use Warexo\Core\Checkout\Cart\DecimalQuantityCartProcessor;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;

require_once __DIR__ . '/../src/Core/Content/Product/Quantity/DecimalQuantityMapper.php';
require_once __DIR__ . '/../src/Core/Checkout/Cart/DecimalQuantityCartData.php';
require_once __DIR__ . '/../src/Core/Checkout/Cart/DecimalQuantityCartProcessor.php';

final class DecimalQuantityCartProcessorTest extends TestCase
{
    #[DataProvider('roundingCases')]
    public function testCartSumsRoundedPositions(string $taxState, string $taxCalculationType, CashRoundingConfig $rounding, float $unitPrice, float $quantity, float $expected): void
    {
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getTaxState')->willReturn($taxState);
        $context->method('getTaxCalculationType')->willReturn($taxCalculationType);
        $context->method('getItemRounding')->willReturn($rounding);
        $context->method('getTotalRounding')->willReturn(new CashRoundingConfig($rounding->getDecimals(), 0.01, true));
        $calculator = $this->calculator();
        $processor = new DecimalQuantityCartProcessor($calculator, new DecimalQuantityMapper(), new CashRounding());
        $cart = new Cart('test');
        $data = new CartDataCollection();
        $lineData = [];
        foreach (['first', 'second'] as $id) {
            $coreQuantity = (new DecimalQuantityMapper())->toCoreQuantity($quantity);
            $definition = new QuantityPriceDefinition($unitPrice / 1000, new TaxRuleCollection([new TaxRule(19)]), $coreQuantity);
            $item = new LineItem($id, LineItem::PRODUCT_LINE_ITEM_TYPE, 'product', $coreQuantity);
            $item->setStackable(true);
            $item->setPriceDefinition($definition);
            $item->setPrice($calculator->calculate($definition, $context));
            $cart->add($item);
            $lineData[$id] = ['decimalQuantity' => $quantity, 'coreQuantity' => $coreQuantity, 'businessUnitPrice' => $unitPrice];
        }
        $data->set(DecimalQuantityCartData::DATA_KEY, $lineData);

        // Recalculation must not accumulate rounding differences or change quantities.
        for ($pass = 0; $pass < 2; ++$pass) {
            $processor->process($data, $cart, $cart, $context, new CartBehavior());
            $prices = new PriceCollection();
            foreach ($cart->getLineItems() as $item) {
                $price = $item->getPrice();
                self::assertNotNull($price);
                self::assertEqualsWithDelta($expected, $price->getTotalPrice(), 0.0000001);
                self::assertSame($unitPrice, $price->getUnitPrice());
                self::assertSame($coreQuantity, $item->getQuantity());
                self::assertSame($quantity, $item->getPayloadValue('warexoDecimalQuantity'));
                if ($taxState === CartPrice::TAX_STATE_FREE) {
                    self::assertCount(0, $price->getCalculatedTaxes());
                } elseif ($calculator->calculate($item->getPriceDefinition(), $context)->getTotalPrice() !== 0.0) {
                    self::assertEqualsWithDelta($expected, $price->getCalculatedTaxes()->first()->getPrice(), 0.0000001);
                }
                $prices->add($price);
            }
            $amount = (new AmountCalculator(new CashRounding(), new PercentageTaxRuleBuilder(), new TaxCalculator()))
                ->calculate($prices, new PriceCollection(), $context);
            self::assertEqualsWithDelta(2 * $expected, $amount->getPositionPrice(), 0.0000001);
            if ($taxState !== CartPrice::TAX_STATE_NET) {
                self::assertEqualsWithDelta(2 * $expected, $amount->getTotalPrice(), 0.0000001);
            }
        }
    }

    public static function roundingCases(): iterable
    {
        foreach ([SalesChannelDefinition::CALCULATION_TYPE_HORIZONTAL, SalesChannelDefinition::CALCULATION_TYPE_VERTICAL] as $calculationType) {
            foreach ([CartPrice::TAX_STATE_GROSS, CartPrice::TAX_STATE_NET, CartPrice::TAX_STATE_FREE] as $state) {
                yield "$calculationType / $state / half" => [$state, $calculationType, new CashRoundingConfig(2, 0.01, true), 24.99, 0.5, 12.50];
                yield "$calculationType / $state / whole" => [$state, $calculationType, new CashRoundingConfig(2, 0.01, true), 24.99, 1.0, 24.99];
            }
        }
        $gross = CartPrice::TAX_STATE_GROSS;
        $horizontal = SalesChannelDefinition::CALCULATION_TYPE_HORIZONTAL;
        yield 'three quantity decimals' => [$gross, $horizontal, new CashRoundingConfig(2, 0.01, true), 24.99, 0.333, 8.32];
        yield 'small scaled unit price' => [$gross, $horizontal, new CashRoundingConfig(2, 0.01, true), 0.99, 0.5, 0.50];
        yield 'three currency decimals' => [$gross, $horizontal, new CashRoundingConfig(3, 0.01, true), 24.99, 0.5, 12.495];
        yield 'gross cash interval' => [$gross, $horizontal, new CashRoundingConfig(2, 0.05, false), 24.93, 0.5, 12.45];
        yield 'net without cash rounding' => [CartPrice::TAX_STATE_NET, $horizontal, new CashRoundingConfig(2, 0.05, false), 24.93, 0.5, 12.47];
        yield 'net with cash rounding' => [CartPrice::TAX_STATE_NET, $horizontal, new CashRoundingConfig(2, 0.05, true), 24.93, 0.5, 12.45];
    }

    public function testSurchargesAndOrdinaryProductRemainStable(): void
    {
        $context = $this->createMock(SalesChannelContext::class);
        $context->method('getTaxState')->willReturn(CartPrice::TAX_STATE_GROSS);
        $context->method('getItemRounding')->willReturn(new CashRoundingConfig(2, 0.01, true));
        $calculator = $this->calculator();
        $processor = new DecimalQuantityCartProcessor($calculator, new DecimalQuantityMapper(), new CashRounding());
        $cart = new Cart('test');
        $data = new CartDataCollection();
        foreach (['decimal', 'ordinary'] as $id) {
            $item = new LineItem($id, LineItem::PRODUCT_LINE_ITEM_TYPE, 'product', 500);
            $item->setStackable(true);
            $definition = new QuantityPriceDefinition(24.99, new TaxRuleCollection([new TaxRule(19)]), 500);
            $item->setPriceDefinition($definition);
            $item->setPrice($calculator->calculate($definition, $context));
            $cart->add($item);
        }
        $ordinaryPrice = $cart->get('ordinary')->getPrice();
        $ordinaryDefinition = $cart->get('ordinary')->getPriceDefinition();
        $decimal = $cart->get('decimal');
        $decimal->setPayloadValue('customFormId', 'form');
        $decimal->setPayloadValue('customFormFields', ['field' => 'value']);
        $data->set('custom-form-price-product-form-' . md5(serialize(['field' => 'value'])), 1.0);
        $data->set('optionValueSelections', ['decimal' => [
            ['surcharge' => ['price' => 2.0, 'type' => 'absolute']],
            ['surcharge' => ['price' => 10.0, 'type' => '%']],
        ]]);
        $data->set(DecimalQuantityCartData::DATA_KEY, ['decimal' => [
            'decimalQuantity' => 0.5, 'coreQuantity' => 500, 'businessUnitPrice' => 24.99,
            'listPrice' => 40.0, 'regulationPrice' => 35.0,
            'referencePriceDefinition' => ['purchaseUnit' => 1.0, 'referenceUnit' => 2.0, 'unitName' => 'm'],
        ]]);

        for ($pass = 0; $pass < 2; ++$pass) {
            $processor->process($data, $cart, $cart, $context, new CartBehavior());
            self::assertEqualsWithDelta(30.589, $decimal->getPrice()->getUnitPrice(), 0.0000001);
            self::assertSame(15.29, $decimal->getPrice()->getTotalPrice());
            self::assertSame(40.0, $decimal->getPrice()->getListPrice()->getPrice());
            self::assertSame(35.0, $decimal->getPrice()->getRegulationPrice()->getPrice());
            self::assertSame(61.178, $decimal->getPrice()->getReferencePrice()->getPrice());
            self::assertSame($ordinaryPrice, $cart->get('ordinary')->getPrice());
            self::assertSame($ordinaryDefinition, $cart->get('ordinary')->getPriceDefinition());
        }
    }

    private function calculator(): QuantityPriceCalculator
    {
        return new QuantityPriceCalculator(
            new GrossPriceCalculator(new TaxCalculator(), new CashRounding()),
            new NetPriceCalculator(new TaxCalculator(), new CashRounding())
        );
    }
}
