<?php declare(strict_types=1);

namespace Warexo\Tests\Unit\Core\Checkout\Cart;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\Cart\ProductStockReachedError;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Warexo\Core\Checkout\Cart\DecimalProductStockErrorValidator;
use Warexo\Core\Checkout\Cart\Error\DecimalProductStockReachedError;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityRequestTransformer;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityValidator;

#[CoversClass(DecimalProductStockErrorValidator::class)]
#[CoversClass(DecimalProductStockReachedError::class)]
final class DecimalProductStockErrorValidatorTest extends TestCase
{
    public function testReplacesValidatorStockQuantityWithDecimalValue(): void
    {
        $productId = 'c0dec0dec0dec0dec0dec0dec0de4003';
        $lineItem = new LineItem($productId, LineItem::PRODUCT_LINE_ITEM_TYPE, $productId, 2200);
        $lineItem->setPayloadValue('warexoIsDecimalQuantity', true);
        $cart = (new Cart('test'))->add($lineItem);
        $errors = new ErrorCollection();
        $errors->add(new ProductStockReachedError($productId, 'Decimal product', 1200, false));
        $mapper = new DecimalQuantityMapper();
        $validator = new DecimalProductStockErrorValidator(
            $mapper,
            new DecimalQuantityRequestTransformer($mapper, new DecimalQuantityValidator())
        );

        $validator->validate($cart, $errors, $this->withoutConstructor(SalesChannelContext::class));

        $error = $errors->get('product-stock-reached' . $productId);
        static::assertInstanceOf(DecimalProductStockReachedError::class, $error);
        static::assertSame(['name' => 'Decimal product', 'quantity' => 1.2], $error->getParameters());
        static::assertSame(DecimalProductStockReachedError::LEVEL_ERROR, $error->getLevel());
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
