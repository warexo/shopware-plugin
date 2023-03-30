<?php

namespace Warexo\Migration;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1680179624AddExtensionIndizes extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1680179624;
    }

    public function update(Connection $connection): void
    {
        $statements = [
            'ALTER TABLE `warexo_product_extension` ADD INDEX `position` (`position`)',
            'ALTER TABLE `warexo_product_extension` ADD FOREIGN KEY (`product_id`) REFERENCES `product` (`id`) ON DELETE CASCADE ON UPDATE CASCADE',
            'TRUNCATE TABLE `warexo_category_extension`',
            'ALTER TABLE `warexo_category_extension` ADD INDEX `position` (`position`)',
            'ALTER TABLE `warexo_category_extension` ADD FOREIGN KEY (`category_id`) REFERENCES `category` (`id`) ON DELETE CASCADE ON UPDATE CASCADE'
        ];

        foreach($statements as $statement) {
            try {
                $connection->executeStatement($statement);
            } catch (\Exception $e) {
                // ignore
            }
        }
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}