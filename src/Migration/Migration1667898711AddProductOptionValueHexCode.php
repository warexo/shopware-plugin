<?php declare(strict_types=1);

namespace Warexo\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1667898711AddProductOptionValueHexCode extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1667898711;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        ALTER TABLE `warexo_product_option_value` ADD `color_hex_code` VARCHAR(20) NULL;
        SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
