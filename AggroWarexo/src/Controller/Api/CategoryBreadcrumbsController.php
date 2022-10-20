<?php declare(strict_types=1);

namespace Warexo\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Shopware\Core\Framework\Context;
use Warexo\Service\CategoryTreeLoader;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class CategoryBreadcrumbsController extends AbstractController
{

    private CategoryTreeLoader $categoryTreeLoader;

    public function __construct(CategoryTreeLoader $categoryTreeLoader)
    {
        $this->categoryTreeLoader = $categoryTreeLoader;
    }

    /**
     * @Route("/api/_action/warexo-category-breadcrumbs", name="api.action.warexo.category-tree", methods={"GET"})
     */
    public function getCategoryBreadcrumbsAction(Request $request, Context $context): JsonResponse
    {
        $result = $this->categoryTreeLoader->load($context);
        $tree = $result->tree;

        return new JsonResponse($this->categoryTreeLoader->load($context));
    }
}