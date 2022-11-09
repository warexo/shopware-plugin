<?php declare(strict_types=1);

namespace Warexo\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1666960592CreateProductToProductOptionTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1666960592;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `warexo_product_to_product_option` (
            `product_id` BINARY(16) NOT NULL,
            `warexo_product_option_id` BINARY(16) NOT NULL,
            PRIMARY KEY (`product_id`, `warexo_product_option_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;
        $connection->executeStatement($sql);
        $sql = <<<'SQL'
        ALTER TABLE `warexo_product_to_product_option`
            ADD CONSTRAINT `fk.warexo_product_to_product_option.product_id`
                    FOREIGN KEY (`product_id`)
                    REFERENCES `product` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE;
        SQL;
        $connection->executeStatement($sql);
        $sql = <<<'SQL'
        ALTER TABLE `warexo_product_to_product_option`
            ADD CONSTRAINT `fk.warexo_product_to_product_option.warexo_product_option_id`
                    FOREIGN KEY (`warexo_product_option_id`)
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
