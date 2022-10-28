<?php declare(strict_types=1);

namespace Warexo\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1666959584CreateProductOptionValueTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1666959584;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `warexo_product_option_value` (
            `id` BINARY(16) NOT NULL,
            `product_option_id` BINARY(16) NOT NULL,
            `media_id` BINARY(16) NOT NULL,
            `display_type` VARCHAR(50) NULL,
            `surcharge` json NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;
        $connection->executeStatement($sql);
        $sql = <<<'SQL'
        ALTER TABLE `warexo_product_option_value`
            ADD CONSTRAINT `fk.warexo_product_option.product_option_id`
                    FOREIGN KEY (`product_option_id`)
                    REFERENCES `warexo_product_option` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE;
        SQL;
        $connection->executeStatement($sql);
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
