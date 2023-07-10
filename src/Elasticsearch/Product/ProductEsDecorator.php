<?php

namespace Warexo\Elasticsearch\Product;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Elasticsearch\Framework\AbstractElasticsearchDefinition;
use Shopware\Elasticsearch\Framework\Indexing\EntityMapper;
use Doctrine\DBAL\Connection;

class ProductEsDecorator extends AbstractElasticsearchDefinition
{
    private AbstractElasticsearchDefinition $productDefinition;
    private Connection $connection;

    public function __construct(AbstractElasticsearchDefinition $productDefinition, Connection $connection)
    {
        $this->productDefinition = $productDefinition;
        $this->connection = $connection;
    }

    public function getEntityDefinition(): EntityDefinition
    {
        return $this->productDefinition->getEntityDefinition();
    }

    /**
     * Extend the mapping with your own changes
     * Take care to get the default mapping first by `$this->productDefinition->getMapping($context);`
     */
    public function getMapping(Context $context): array
    {
        $mapping = $this->productDefinition->getMapping($context);

        // Adding an association as keyword
        $mapping['properties']['warexoExtension'] = [
            'type' => 'nested',
            'properties' => [
                'position' => EntityMapper::INT_FIELD,
            ],
        ];

        return $mapping;
    }

    public function fetch(array $ids, Context $context): array
    {
        $documents = $this->productDefinition->fetch($ids, $context);

        $associationOneToOne = $this->fetchOneToOne($ids);

        foreach ($documents as &$document) {

            /**
             * Field with value from associated entity
             */
            if (isset($associationOneToOne[$document['id']])) {
                $document['warexoExtension']['position'] = $associationOneToOne[$document['id']];
            }
        }

        return $documents;
    }

    /**
     * Read the associated entries directly from the database
     */
    private function fetchOneToOne(array $ids): array
    {
        $query = <<<SQL
            SELECT LOWER(HEX(product_id)) as id, position
            FROM warexo_product_extension
            WHERE
                product_id IN(:ids)
        SQL;


        return $this->connection->fetchAllKeyValue(
            $query,
            [
                'ids' => $ids,
            ],
            [
                'ids' => Connection::PARAM_STR_ARRAY
            ]
        );
    }

}