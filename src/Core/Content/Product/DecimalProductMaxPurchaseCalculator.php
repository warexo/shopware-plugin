<?php declare(strict_types=1);

namespace Warexo\Core\Content\Product;

use Shopware\Core\Content\Product\AbstractProductMaxPurchaseCalculator;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityFeatureDecider;
use Warexo\Core\Content\Product\Quantity\DecimalQuantityMapper;

class DecimalProductMaxPurchaseCalculator extends AbstractProductMaxPurchaseCalculator
{
    private const MAX_QUANTITY_CONFIG_KEY = 'core.cart.maxQuantity';

    public function __construct(
        private readonly AbstractProductMaxPurchaseCalculator $inner,
        private readonly SystemConfigService $systemConfigService,
        private readonly DecimalQuantityFeatureDecider $featureDecider,
        private readonly DecimalQuantityMapper $quantityMapper
    ) {
    }

    public function getDecorated(): AbstractProductMaxPurchaseCalculator
    {
        return $this->inner;
    }

    public function calculate(Entity $product, SalesChannelContext $context): int
    {
        if (!$this->featureDecider->isEnabled($context->getSalesChannelId())) {
            return $this->inner->calculate($product, $context);
        }

        if ($product->get('maxPurchase') !== null) {
            return $this->inner->calculate($product, $context);
        }

        $fallback = $this->quantityMapper->toCoreQuantity((float) $this->systemConfigService->getInt(
            self::MAX_QUANTITY_CONFIG_KEY,
            $context->getSalesChannelId()
        ));

        if ($product->get('isCloseout') && $product->get('stock') < $fallback) {
            $fallback = (int) $product->get('stock');
        }

        $steps = $product->get('purchaseSteps') ?? 1;
        $min = $product->get('minPurchase') ?? 1;

        $fallback = \floor(($fallback - $min) / $steps) * $steps + $min;

        return (int) \max($fallback, 0);
    }
}