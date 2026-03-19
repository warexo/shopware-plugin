<?php declare(strict_types=1);

namespace Warexo\Core\Content\Product\Quantity;

use Shopware\Core\System\SystemConfig\SystemConfigService;

class DecimalQuantityFeatureDecider
{
    private const CONFIG_KEY = 'AggroWarexoPlugin.config.decimalstock';

    public function __construct(
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    public function isEnabled(?string $salesChannelId = null): bool
    {
        return (bool) $this->systemConfigService->get(self::CONFIG_KEY, $salesChannelId);
    }
}