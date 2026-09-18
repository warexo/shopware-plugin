<?php declare(strict_types=1);

namespace Warexo\Tests\Unit\Core\Checkout\Cart;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Warexo\Core\Checkout\Cart\DecimalQuantityCartData;

#[CoversClass(DecimalQuantityCartData::class)]
final class DecimalQuantityCartDataTest extends TestCase
{
    public function testReturnsDataForTheRequestedLineItem(): void
    {
        $data = new CartDataCollection();
        $lineItem = new LineItem('line-item', LineItem::PRODUCT_LINE_ITEM_TYPE, 'product-id');
        $lineItemData = ['decimalQuantity' => 1.25, 'coreQuantity' => 1250];
        $data->set(DecimalQuantityCartData::DATA_KEY, ['line-item' => $lineItemData]);

        static::assertSame($lineItemData, DecimalQuantityCartData::get($data, $lineItem));
        static::assertTrue(DecimalQuantityCartData::has($data, $lineItem));
    }

    public function testRejectsMissingOrMalformedCartData(): void
    {
        $data = new CartDataCollection();
        $lineItem = new LineItem('line-item', LineItem::PRODUCT_LINE_ITEM_TYPE, 'product-id');

        static::assertNull(DecimalQuantityCartData::get($data, $lineItem));
        static::assertFalse(DecimalQuantityCartData::has($data, $lineItem));

        $data->set(DecimalQuantityCartData::DATA_KEY, ['line-item' => 'invalid']);

        static::assertNull(DecimalQuantityCartData::get($data, $lineItem));
    }
}
