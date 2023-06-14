<?php declare(strict_types=1);

namespace Warexo\Service;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\PrefixFilter;
use Shopware\Storefront\Page\Navigation\NavigationPageLoaderInterface;
use Shopware\Storefront\Page\Navigation\NavigationPage;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Content\Category\SalesChannel\AbstractCategoryRoute;
use Shopware\Core\Content\Seo\AbstractSeoResolver;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
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
    private EntityRepository $salesChannelDomainRepository;

    public function __construct(
        NavigationPageLoaderInterface $navigationPageLoader,
        AbstractCategoryRoute $cmsPageRoute,
        AbstractSeoResolver $resolver,
        EntityRepository $salesChannelDomainRepository
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
            if ( $this->isCanonicalCategory($customFields) ) {
                $salesChannelId = isset($customFields['custom_warexo_canonical_saleschannel']) && $customFields['custom_warexo_canonical_saleschannel'] ? $customFields['custom_warexo_canonical_saleschannel'] : $context->getSalesChannel()->getId();
                $seoUrl = $this->resolver->resolve($context->getLanguageId(), $salesChannelId, '/navigation/'.$customFields['custom_warexo_canonical_category']);
                if ($seoUrl && isset($seoUrl['canonicalPathInfo'])) {
                    $domain = $this->findSalesChannelDomainUrl($salesChannelId, $context->getContext(), $request->isSecure());
                    if ($domain) {
                        $page->getMetaInformation()->setCanonical($domain.$seoUrl['canonicalPathInfo']);
                    }
                }
            }
        }

        return $page;
    }

    private function findSalesChannelDomainUrl(string $salesChannelId, $context, $secure): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        $criteria->addFilter(new EqualsFilter('languageId', $context->getLanguageId()));
        $criteria->addFilter(new EqualsFilter('currencyId', $context->getCurrencyId()));
        $criteria->addFilter(new PrefixFilter('url', $secure ? 'https://' : 'http://'));

        $result = $this->salesChannelDomainRepository->search($criteria, $context);

        if ($result->getTotal() === 0) {
            return null;
        }

        return $result->first()->getUrl();
    }

    /**
     * @param array|null $customFields
     * @return bool
     */
    private function isCanonicalCategory(?array $customFields): bool
    {
        return
            isset($customFields['custom_warexo_canonical_category']) &&
            $customFields['custom_warexo_canonical_category'] &&
            (
                !isset($customFields['custom_warexo_canonical_mode']) ||
                $customFields['custom_warexo_canonical_mode'] === 'category' ||
                $customFields['custom_warexo_canonical_mode'] === ''
            );
    }
}