<?php declare(strict_types=1);

namespace Warexo\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Shopware\Core\Framework\Context;
use Symfony\Component\Routing\Attribute\Route;
use Warexo\Service\CategoryTreeLoader;

#[Route(defaults: ['_routeScope' => ['api']])]
class CategoryTreeController extends AbstractController
{

    private CategoryTreeLoader $categoryTreeLoader;

    public function __construct(CategoryTreeLoader $categoryTreeLoader)
    {
        $this->categoryTreeLoader = $categoryTreeLoader;
    }

    #[Route(path: '/api/_action/warexo-category-list', name: 'api.action.warexo.category.list', methods: ['GET'])]
    public function getCategoryListAction(Request $request, Context $context): JsonResponse
    {
        return new JsonResponse($this->categoryTreeLoader->loadFlat($context));
    }

    //@TODO: add tree capabilities to warexo to display this
    /**
    #[Route(path: '/api/_action/warexo-category-tree', name: 'api.action.warexo.category-tree', methods: ['GET'])]
    public function getCategoryTreeAction(Request $request, Context $context): JsonResponse
    {
        return new JsonResponse($this->categoryTreeLoader->load($context));
    }
    */
}