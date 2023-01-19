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
            `warexo_product_option_id` BINARY(16) NOT NULL,
            `media_id` BINARY(16) NULL,
            `position` int(10) NOT NULL,
            `surcharge` json NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;
        try{
            $connection->executeStatement($sql);
        }catch (\Exception $e){

        }
        $sql = <<<'SQL'
        ALTER TABLE `warexo_product_option_value`
            ADD CONSTRAINT `fk.warexo_product_option.warexo_product_option_id`
                    FOREIGN KEY (`warexo_product_option_id`)
                    REFERENCES `warexo_product_option` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE;
        SQL;
        try{
            $connection->executeStatement($sql);
        }catch (\Exception $e){

        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
