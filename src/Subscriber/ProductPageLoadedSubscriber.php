<?php

namespace Warexo\Subscriber;

use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaCollection;
use Shopware\Core\Content\Product\Aggregate\ProductMedia\ProductMediaEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Seo\AbstractSeoResolver;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
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
        $this->handleCanonicalSettings($event);
        $this->addDownloadableMedia($event);
    }

    private function handleCanonicalSettings(ProductPageLoadedEvent $event)
    {
        $context = $event->getContext();
        $page = $event->getPage();
        $product = $page->getProduct();
        $category = $product->getSeoCategory();

        if ($category) {
            $customFields = $category->getCustomFields();
            if ( $this->isCanonicalProduct($customFields) ) {
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

    private function addDownloadableMedia(ProductPageLoadedEvent $event)
    {
        $page = $event->getPage();
        if ($page->getProduct()) {
            $productId = $page->getProduct()->getParentId() ?: $page->getProduct()->getId();

            $criteria = new Criteria([$productId]);
            $criteria->addAssociation('media');
            $criteria->addAssociation('media.media');
            $product = $this->productRepository->search($criteria, $event->getContext())->first();

            if ($product->getMedia()) {
                $product->getMedia()->sort(function (ProductMediaEntity $a, ProductMediaEntity $b) {
                    return $a->getPosition() <=> $b->getPosition();
                });
                $page->addExtension('downloadableMedia', $product->getMedia()->filter(function($media) {
                    return str_starts_with($media->getMedia()->getMimeType(), 'image/') === false;
                }));
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

    /**
     * @param array|null $customFields
     * @return bool
     */
    private function isCanonicalProduct(?array $customFields): bool
    {
        return
            isset($customFields['custom_warexo_canonical_category']) &&
            $customFields['custom_warexo_canonical_category'] &&
            (
                !isset($customFields['custom_warexo_canonical_mode']) ||
                $customFields['custom_warexo_canonical_mode'] === 'product' ||
                $customFields['custom_warexo_canonical_mode'] === ''
            );
    }
}