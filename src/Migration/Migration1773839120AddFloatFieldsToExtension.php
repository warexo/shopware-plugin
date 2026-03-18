<?php declare(strict_types=1);

namespace Warexo\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * @internal
 */
class Migration1773839120AddFloatFieldsToExtension extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1773839120;
    }

    public function update(Connection $connection): void
    {
        $connection->executeStatement('ALTER TABLE `warexo_product_extension` ADD COLUMN `stock` DECIMAL(10,3) UNSIGNED NULL');
        $connection->executeStatement('ALTER TABLE `warexo_product_extension` ADD COLUMN `min_purchase` DECIMAL(10,3) UNSIGNED NULL');
        $connection->executeStatement('ALTER TABLE `warexo_product_extension` ADD COLUMN `max_purchase` DECIMAL(10,3) UNSIGNED NULL');
        $connection->executeStatement('ALTER TABLE `warexo_product_extension` ADD COLUMN `purchase_steps` DECIMAL(10,3) UNSIGNED NULL');
    }
}
