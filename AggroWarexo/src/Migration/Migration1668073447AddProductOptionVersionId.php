<?php declare(strict_types=1);

namespace Warexo\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1668073447AddProductOptionVersionId extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1668073447;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        ALTER TABLE `warexo_product_to_product_option` ADD `product_version_id` BINARY(16) NOT NULL;
        SQL;
        $connection->executeStatement($sql);
        $sql = <<<SQL
        ALTER TABLE `warexo_product_to_product_option` DROP PRIMARY KEY, ADD PRIMARY KEY (`product_id`, `product_version_id`, `warexo_product_option_id`);
        SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
