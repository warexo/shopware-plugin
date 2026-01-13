<?php

namespace Warexo\Elasticsearch\Product;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use OpenSearchDSL\Query\Compound\BoolQuery;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Elasticsearch\Framework\AbstractElasticsearchDefinition;

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

    public function buildTermQuery(Context $context, Criteria $criteria): BoolQuery
    {
        return $this->productDefinition->buildTermQuery($context, $criteria);
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
                'position' => self::INT_FIELD,
            ],
        ];

        return $mapping;
    }

    public function fetch(array $ids, Context $context): array
    {
        $documents = $this->productDefinition->fetch($ids, $context);

        $associationOneToOne = $this->fetchOneToOnePosition($ids);

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
    private function fetchOneToOnePosition(array $ids): array
    {
        $query = <<<SQL
            SELECT LOWER(HEX(product_id)) as id, `position`
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
                'ids' => ArrayParameterType::STRING,
            ]
        );
    }

}