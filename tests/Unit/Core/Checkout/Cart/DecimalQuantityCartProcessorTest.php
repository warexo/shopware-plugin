<?php declare(strict_types=1);

namespace Warexo\Tests\Unit\Core\Checkout\Cart;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Tax\TaxCalculator;
use Shopware\Core\Content\Product\Cart\ProductStockReachedError;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
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
            $this->withoutConstructor(TaxCalculator::class)
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
