<?php declare(strict_types=1);

namespace Warexo\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1666959348CreateProductOptionTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1666959348;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS `warexo_product_option` (
            `id` BINARY(16) NOT NULL,
            `display_type` VARCHAR(50) NULL,
            `position` INT(10) NULL,
            `ident` VARCHAR(255) NULL,
            `created_at` DATETIME(3) NOT NULL,
            `updated_at` DATETIME(3) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
