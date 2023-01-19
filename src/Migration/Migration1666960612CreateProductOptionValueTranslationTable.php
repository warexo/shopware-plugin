<?php declare(strict_types=1);

namespace Warexo\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1666960612CreateProductOptionValueTranslationTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1666960612;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `warexo_product_option_value_translation` (
            `warexo_product_option_value_id` BINARY(16) NOT NULL,
            `language_id` BINARY(16) NOT NULL,
            `name` VARCHAR(255) NOT NULL,
            `description` TEXT NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`warexo_product_option_value_id`, `language_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;
        try{
            $connection->executeStatement($sql);
        }catch (\Exception $e){

        }
        $sql = <<<'SQL'
        ALTER TABLE `warexo_product_option_value_translation`
            ADD CONSTRAINT `fk.warexo_product_option_value_trans.product_option_value_id`
                    FOREIGN KEY (`warexo_product_option_value_id`)
                    REFERENCES `warexo_product_option_value` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE;
        SQL;
        try{
            $connection->executeStatement($sql);
        }catch (\Exception $e){

        }
        $sql = <<<'SQL'
        ALTER TABLE `warexo_product_option_value_translation`
            ADD CONSTRAINT `fk.warexo_product_option_value_translation.language_id`
                    FOREIGN KEY (`language_id`)
                    REFERENCES `language` (`id`)
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
