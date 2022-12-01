<?php declare(strict_types=1);

namespace Warexo\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1667812036AddProductOptionCustomFields extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1667812036;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        ALTER TABLE `warexo_product_option_translation` ADD `custom_fields` JSON;
        SQL;
        $connection->executeStatement($sql);

        $sql = <<<SQL
        ALTER TABLE `warexo_product_option_value_translation` ADD `custom_fields` JSON;
        SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
