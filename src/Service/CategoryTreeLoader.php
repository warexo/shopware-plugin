<?php declare(strict_types=1);

namespace Warexo\Service;

use Shopware\Core\Content\Category\Tree\Tree;
use Shopware\Core\Content\Category\Tree\TreeItem;
use Shopware\Core\Content\Category\Event\NavigationLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Util\AfterSort;
use Shopware\Core\Framework\Context;

class CategoryTreeLoader
{

    private EntityRepository $categoryRepository;
    private EventDispatcherInterface $eventDispatcher;
    private TreeItem $treeItem;

    public function __construct(
        EntityRepository $categoryRepository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->eventDispatcher = $eventDispatcher;

        $this->treeItem = new TreeItem(null, []);
    }

    public function load(Context $context): Tree
    {
        $categories = $this->categoryRepository->search(new Criteria(), $context);
        return $this->getTree('', $categories);
    }

    public function loadFlat(Context $context): array
    {
        $categories = $this->categoryRepository->search(new Criteria(), $context);
        $breadcrumbs = [];
        foreach($categories as $category) {
            $breadcrumbs[$category->getId()] = implode(' > ', $category->getBreadcrumb());
        }
        asort($breadcrumbs);
        return $breadcrumbs;
    }

    private function getTree(?string $rootId, EntityCollection $categories): Tree
    {
        $parents = [];
        $items = [];
        foreach ($categories as $category) {
            $item = clone $this->treeItem;
            $item->setCategory($category);

            $parents[$category->getParentId()][$category->getId()] = $item;
            $items[$category->getId()] = $item;
        }

        foreach ($parents as $parentId => $children) {
            if (empty($parentId)) {
                continue;
            }

            $sorted = AfterSort::sort($children);

            if (!isset($items[$parentId])) {
                continue;
            }

            $item = $items[$parentId];
            $item->setChildren($sorted);
        }

        $root = $parents[$rootId] ?? [];
        $root = AfterSort::sort($root);

        return new Tree(null, $root);
    }
}