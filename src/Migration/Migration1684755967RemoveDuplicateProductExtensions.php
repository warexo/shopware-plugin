<?php

namespace Warexo\Migration;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1684755967RemoveDuplicateProductExtensions extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1684755967;
    }

    public function update(Connection $connection): void
    {
        $statements = [
            'TRUNCATE TABLE `warexo_product_extension`'
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