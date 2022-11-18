<?php

namespace Warexo\Subscriber;

use Shopware\Core\Content\Seo\AbstractSeoResolver;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
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
                    $seoUrl = $this->resolver->resolve($context->getLanguageId(), $salesChannelId, '/detail/'.$product->getId());
                    if ($seoUrl && isset($seoUrl['canonicalPathInfo'])) {
                        $domain = $this->findSalesChannelUrl($salesChannelId, $context);
                        if ($domain) {
                            $page->getMetaInformation()->setCanonical($domain.$seoUrl['canonicalPathInfo']);
                        }
                    }
                }

            }
        }

        if ($product->getParentId()) {
            $criteria = new Criteria([$product->getParentId()]);
            $criteria->addAssociation('media');
            $parent = $this->productRepository->search($criteria, $context)->first();
            $product->getMedia()->merge($parent->getMedia());
        }
    }

    private function findSalesChannelUrl(string $salesChannelId, $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('salesChannelId', $salesChannelId));
        $salesChannelDomain = $this->salesChannelDomainRepository->search($criteria, $context)->first();
        return $salesChannelDomain ? $salesChannelDomain->getUrl() : null;
    }
}