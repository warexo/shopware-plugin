<?php declare(strict_types=1);

namespace Warexo\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1667812743AddProductOptionDescription extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1667812743;
    }

    public function update(Connection $connection): void
    {
        $sql = <<<SQL
        ALTER TABLE `warexo_product_option_translation` ADD `description` TEXT NULL;
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
