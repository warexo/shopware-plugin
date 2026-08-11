<?php declare(strict_types=1);

namespace Warexo\Core\Checkout\Cart\Error;

use Shopware\Core\Checkout\Cart\Error\Error;

class DecimalProductStockReachedError extends Error
{
    public function __construct(
        private readonly string $productId,
        private readonly string $name,
        private readonly float $quantity,
        private readonly bool $resolved
    ) {
        parent::__construct(sprintf(
            'The product %s is only available %s times',
            $name,
            rtrim(rtrim(number_format($quantity, 3, '.', ''), '0'), '.')
        ));
    }

    public function getParameters(): array
    {
        return ['name' => $this->name, 'quantity' => $this->quantity];
    }

    public function getId(): string
    {
        return $this->getMessageKey() . $this->productId;
    }

    public function getMessageKey(): string
    {
        return 'product-stock-reached';
    }

    public function getLevel(): int
    {
        return $this->resolved ? self::LEVEL_WARNING : self::LEVEL_ERROR;
    }

    public function blockOrder(): bool
    {
        return true;
    }

    public function isPersistent(): bool
    {
        return $this->resolved;
    }
}
