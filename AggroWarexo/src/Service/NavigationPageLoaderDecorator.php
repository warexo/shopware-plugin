<?php declare(strict_types=1);

namespace Warexo\Service;

use Shopware\Storefront\Page\Navigation\NavigationPageLoaderInterface;
use Shopware\Storefront\Page\Navigation\NavigationPage;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Content\Category\SalesChannel\AbstractCategoryRoute;
use Shopware\Core\Content\Seo\AbstractSeoResolver;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

/**
 * Class NavigationPageLoaderDecorator
 * @package Warexo\Service
 * Load main canonical url for navigation pages if given
 */
class NavigationPageLoaderDecorator implements NavigationPageLoaderInterface
{
    private NavigationPageLoaderInterface $decoratedService;
    private AbstractCategoryRoute $cmsPageRoute;
    private AbstractSeoResolver $resolver;
    private EntityRepositoryInterface $salesChannelDomainRepository;

    public function __construct(
        NavigationPageLoaderInterface $navigationPageLoader,
        AbstractCategoryRoute $cmsPageRoute,
        AbstractSeoResolver $resolver,
        EntityRepositoryInterface $salesChannelDomainRepository
    )
    {
        $this->decoratedService = $navigationPageLoader;
        $this->cmsPageRoute = $cmsPageRoute;
        $this->resolver = $resolver;
        $this->salesChannelDomainRepository = $salesChannelDomainRepository;
    }

    public function getDecorated(): NavigationPageLoaderInterface
    {
        return $this->decoratedService;
    }

    public function load(Request $request, SalesChannelContext $context): NavigationPage
    {

        $page = $this->decoratedService->load($request, $context);
        $navigationId = $request->get('navigationId', $context->getSalesChannel()->getNavigationCategoryId());

        $category = $this->cmsPageRoute
            ->load($navigationId, $request, $context)
            ->getCategory();

        if ($page->getMetaInformation()) {
            $customFields = $category->getCustomFields();
            if (isset($customFields['custom_warexo_canonical_category']) && $customFields['custom_warexo_canonical_category']) {
                $salesChannelId = isset($customFields['custom_warexo_canonical_saleschannel']) && $customFields['custom_warexo_canonical_saleschannel'] ? $customFields['custom_warexo_canonical_saleschannel'] : $context->getSalesChannel()->getId();
                $seoUrl = $this->resolver->resolve($context->getLanguageId(), $salesChannelId, '/navigation/'.$customFields['custom_warexo_canonical_category']);
                if ($seoUrl && isset($seoUrl['canonicalPathInfo'])) {
                    $domain = $this->findSalesChannelUrl($salesChannelId, $context->getContext());
                    if ($domain) {
                        $page->getMetaInformation()->setCanonical($domain.$seoUrl['canonicalPathInfo']);
                    }
                }
            }
        }

        return $page;
    }

    private function findSalesChannelUrl(string $salesChannelId, $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        $salesChannelDomain = $this->salesChannelDomainRepository->search($criteria, $context)->first();
        return $salesChannelDomain ? $salesChannelDomain->getUrl() : null;
    }
}