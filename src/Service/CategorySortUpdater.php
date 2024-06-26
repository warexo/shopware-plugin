<?php

namespace Warexo\Service;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\Query\QueryBuilder;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\EntityDefinitionQueryHelper;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\RetryableTransaction;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\TreeUpdater;

class CategorySortUpdater extends TreeUpdater
{
    public function __construct(
        private readonly DefinitionInstanceRegistry $registry,
        private readonly Connection $connection,
        private readonly TreeUpdater $decoratedService
    )
    {
    }

    public function getDecorated(): TreeUpdater
    {
        return $this->decoratedService;
    }

    public function batchUpdate(array $updateIds, string $entity, Context $context, bool $recursive = false): void
    {
        $this->decoratedService->batchUpdate($updateIds, $entity, $context , $recursive);

        $updateIds = Uuid::fromHexToBytesList(array_unique($updateIds));

        if (empty($updateIds) || $entity !== 'category') {
            return;
        }

        // the batch update does not support versioning, so fallback to single updates
        foreach ($updateIds as $id) {
            $this->singleUpdate(Uuid::fromBytesToHex($id), $entity, $context);
        }

    }

    private function singleUpdate(string $entityId, string $entity, Context $context): void
    {
        $definition = $this->registry->getByEntityName($entity);

        $entity = $this->loadEntity(Uuid::fromHexToBytes($entityId), $definition, Uuid::fromHexToBytes($context->getVersionId()));

        if ($entity === []) {
            return;
        }

        if ($entity['parent_id']) {
            $parent = $this->loadEntity(
                $entity['parent_id'],
                $definition,
                $entity['parent_version_id'] ?: Uuid::fromHexToBytes($context->getVersionId())
            );

            if ($parent === []) {
                return;
            }
        }else{
            return;
        }

        $this->updateParent($parent, $definition, $context);
    }

    private function updateParent(array $parent, EntityDefinition $definition, Context $context): void
    {
        $children = $this->getChildren($parent, $definition, $context);
        $sortedChildren = $this->getSortedChildren($children);

        foreach($children as $child) {
            $position = intval(array_search($child['id'], $sortedChildren));
            $extension = $this->getWarexoExtension($child['id']);

            if ($extension) {
                if ($extension['position'] !== $position) {
                    $sql = 'UPDATE warexo_category_extension SET position = :position, updated_at = :updated_at WHERE id = :id';
                    $statement = $this->connection->prepare($sql);
                    $statement->execute([
                        'position' => $position,
                        'id' => $extension['id'],
                        'updated_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    ]);
                }
            }else{
                $sql = 'INSERT INTO warexo_category_extension (id, category_id, position, created_at, updated_at) VALUES (:id, :category_id, :position, :created_at, :updated_at)';
                $statement = $this->connection->prepare($sql);
                $statement->execute([
                    'id' => Uuid::randomBytes(),
                    'category_id' => $child['id'],
                    'position' => $position,
                    'created_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                    'updated_at' => (new \DateTime())->format(Defaults::STORAGE_DATE_TIME_FORMAT),
                ]);
            }
        }
    }

    private function getSortedChildren($children) {

        $firstChild = current(array_filter($children, function($child) {
            return $child['after_category_id'] === null;
        }));
        if ($firstChild) {
            $sortedChildren = [$firstChild['id']];

            while($nextChild = $this->findNextChild($children, $firstChild['id'])) {
                $sortedChildren[] = $nextChild['id'];
                $firstChild = $nextChild;
            }
        }else{
            $sortedChildren = array_map(function($child) {
                return $child['id'];
            }, $children);
        }

        return $sortedChildren;
    }

    private function findNextChild($children, $afterCategoryId) {
        foreach($children as $child) {
            if ($child['after_category_id'] === $afterCategoryId) {
                return $child;
            }
        }
    }

    private function getChildren(array $parent, EntityDefinition $definition, Context $context): array
    {
        $query = $this->connection->createQueryBuilder();
        $escaped = EntityDefinitionQueryHelper::escape($definition->getEntityName());
        $query->from($escaped);

        $query->select($this->getFieldsToSelect($definition));
        if ($parent === []) {
            $query->andWhere('parent_id is null');
        }else{
            $query->andWhere('parent_id = :id');
            $query->setParameter('id', $parent['id']);
            $this->makeQueryVersionAware($definition, Uuid::fromHexToBytes($context->getVersionId()), $query);
        }

        return $query->execute()->fetchAll();
    }

    private function makeQueryVersionAware(EntityDefinition $definition, string $versionId, QueryBuilder $query): void
    {
        if ($definition->isVersionAware()) {
            $query->andWhere('version_id = :versionId');
            $query->setParameter('versionId', $versionId);
        }
    }

    private function getFieldsToSelect(EntityDefinition $definition): array
    {
        $fields = ['id', 'parent_id', 'after_category_id'];

        if ($definition->isVersionAware()) {
            $fields[] = 'version_id';
            $fields[] = 'parent_version_id';
        }

        return $fields;
    }
    private function loadEntity(string $entity, EntityDefinition $definition, string $versionId = null): array
    {
        $query = $this->getEntityByIdQuery($entity, $definition);
        $this->makeQueryVersionAware($definition, $versionId, $query);

        $result = $query->execute()->fetch();

        if ($result === false) {
            return [];
        }

        return $result;
    }

    private function getEntityByIdQuery(string $id, EntityDefinition $definition): QueryBuilder
    {
        $query = $this->connection->createQueryBuilder();
        $escaped = EntityDefinitionQueryHelper::escape($definition->getEntityName());

        $query->from($escaped);

        $query->select($this->getFieldsToSelect($definition));
        $query->andWhere('id = :id');
        $query->setParameter('id', $id);

        return $query;
    }

    private function getWarexoExtension(string $id)
    {
        $query = $this->connection->createQueryBuilder();
        $escaped = EntityDefinitionQueryHelper::escape('warexo_category_extension');

        $query->from($escaped);

        $query->select(['id', 'category_id', 'position']);
        $query->andWhere('category_id = :id');
        $query->setParameter('id', $id);

        return $query->execute()->fetch();
    }

 }