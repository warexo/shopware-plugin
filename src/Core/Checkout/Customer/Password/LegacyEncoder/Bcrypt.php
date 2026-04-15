<?php declare(strict_types=1);

namespace Warexo\Core\Checkout\Customer\Password\LegacyEncoder;

use Shopware\Core\Checkout\Customer\Password\LegacyEncoder\LegacyEncoderInterface;
use Shopware\Core\Framework\Log\Package;

#[Package('checkout')]
class Bcrypt implements LegacyEncoderInterface
{
    public function getName(): string
    {
        return 'Bcrypt';
    }

    public function isPasswordValid(#[\SensitiveParameter] string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}