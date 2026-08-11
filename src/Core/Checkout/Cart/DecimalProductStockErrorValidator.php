<?php declare(strict_types=1);

namespace Warexo\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartValidatorInterface;
use Shopware\Core\Checkout\Cart\Error\ErrorCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Content\Product\Cart\ProductStockReachedError;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Warexo\Core\Checkout\Cart\Error\DecimalProductStockReachedError;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityRequestTransformer;

class DecimalProductStockErrorValidator implements CartValidatorInterface
{
    public function __construct(
        private readonly DecimalQuantityMapper $quantityMapper,
        private readonly DecimalQuantityRequestTransformer $requestTransformer
    ) {
    }

    public function validate(Cart $cart, ErrorCollection $errors, SalesChannelContext $context): void
    {
        foreach ($cart->getLineItems()->filterFlatByType(LineItem::PRODUCT_LINE_ITEM_TYPE) as $lineItem) {
            if (!$lineItem instanceof LineItem || !$this->requestTransformer->isTruthy($lineItem->getPayloadValue('warexoIsDecimalQuantity'))) {
                continue;
            }

            $referencedId = $lineItem->getReferencedId();
            if ($referencedId === null) {
                continue;
            }

            $error = $errors->get('product-stock-reached' . $referencedId);
            if (!$error instanceof ProductStockReachedError) {
                continue;
            }

            $errors->add(new DecimalProductStockReachedError(
                $referencedId,
                $error->getName(),
                $this->quantityMapper->fromCoreQuantity($error->getQuantity()),
                $error->isPersistent()
            ));
        }
    }
}
