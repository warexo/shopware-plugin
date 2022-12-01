<?php

namespace Warexo\Subscriber;

use Shopware\Core\Content\Seo\AbstractSeoResolver;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\PrefixFilter;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductPageLoadedSubscriber implements EventSubscriberInterface
{
    private AbstractSeoResolver $resolver;
    private EntityRepositoryInterface $salesChannelDomainRepository;
    private EntityRepositoryInterface $productRepository;

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded'
        ];
    }

    public function __construct(
        AbstractSeoResolver $resolver,
        EntityRepositoryInterface $salesChannelDomainRepository,
        EntityRepositoryInterface $productRepository
    )
    {
        $this->resolver = $resolver;
        $this->salesChannelDomainRepository = $salesChannelDomainRepository;
        $this->productRepository = $productRepository;
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event)
    {
        $context = $event->getContext();
        $page = $event->getPage();
        $product = $page->getProduct();
        $category = $product->getSeoCategory();

        if ($category) {
            $customFields = $category->getCustomFields();
            if (isset($customFields['custom_warexo_canonical_category']) && $customFields['custom_warexo_canonical_category']) {
                $salesChannelId = isset($customFields['custom_warexo_canonical_saleschannel']) && $customFields['custom_warexo_canonical_saleschannel'] ? $customFields['custom_warexo_canonical_saleschannel'] : $context->getSource()->getId();
                if ($salesChannelId) {
                    $productId = $product->getParentId() ?: $product->getId();
                    $seoUrl = $this->resolver->resolve($context->getLanguageId(), $salesChannelId, '/detail/'.$productId);
                    if ($seoUrl && isset($seoUrl['canonicalPathInfo'])) {
                        $domain = $this->findSalesChannelDomainUrl($salesChannelId, $context, $event->getRequest()->isSecure());
                        if ($domain) {
                            $page->getMetaInformation()->setCanonical($domain.$seoUrl['canonicalPathInfo']);
                        }
                    }
                }

            }
        }

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
}