<?php declare(strict_types=1);

namespace Warexo\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\InheritanceUpdaterTrait;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1668077337UpdateProductInheritance extends MigrationStep
{
    use InheritanceUpdaterTrait;

    public function getCreationTimestamp(): int
    {
        return 1668077337;
    }

    public function update(Connection $connection): void
    {
        $this->updateInheritance($connection, 'product', 'warexoProductOptions');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
