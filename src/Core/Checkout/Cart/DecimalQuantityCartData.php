<?php declare(strict_types=1);

namespace Warexo\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;

final class DecimalQuantityCartData
{
    public const DATA_KEY = 'warexoDecimalLineItems';
    public const BUSINESS_UNIT_PRICE_DATA_KEY = 'warexoBusinessUnitPrices';

    /**
     * @return array<string, mixed>|null
     */
    public static function get(CartDataCollection $data, LineItem $lineItem): ?array
    {
        $lineItems = $data->get(self::DATA_KEY);
        if (!is_array($lineItems)) {
            return null;
        }

        $lineItemData = $lineItems[$lineItem->getId()] ?? null;

        return is_array($lineItemData) ? $lineItemData : null;
    }

    public static function has(CartDataCollection $data, LineItem $lineItem): bool
    {
        return self::get($data, $lineItem) !== null;
    }
}
