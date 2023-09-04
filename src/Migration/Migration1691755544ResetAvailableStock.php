<?php

declare(strict_types=1);

namespace Warexo\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1691755544ResetAvailableStock extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1691755544;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('UPDATE product SET available_stock = stock');
    }

    public function updateDestructive(Connection $connection): void
    {
    }
}