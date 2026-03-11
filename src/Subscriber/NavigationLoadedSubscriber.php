<?php

namespace Warexo\Subscriber;

use Shopware\Core\Content\Category\Event\NavigationLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NavigationLoadedSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly EntityRepository $mediaRepository)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            NavigationLoadedEvent::class => 'onNavigationLoaded'
        ];
    }

    public function onNavigationLoaded(NavigationLoadedEvent $event): void
    {
        $navigation = $event->getNavigation();
        $mediaCategories = $this->findMediaCategories($navigation->getTree());
        $mediaIds = array_keys($mediaCategories);
        if (!empty($mediaIds)) {
            $criteria = new Criteria($mediaIds);
            $medias = $this->mediaRepository->search($criteria, $event->getContext())->getEntities();
            foreach($mediaCategories as $mediaId => $category) {
                $media = $medias->get($mediaId);
                if ($media) {
                    $category->addExtension('warexoIcon', $media);
                }
            }
        }
    }

    private function findMediaCategories($navigation, $mediaCategories = []): array
    {
        foreach ($navigation as $item) {
            $category = $item->getCategory();
            $customFields = $category->getCustomFields();
            if ($customFields && isset($customFields['custom_warexo_icon'])) {
                $mediaCategories[$customFields['custom_warexo_icon']] = $category;
            }
            if ($category->getChildren()) {
                $mediaCategories = $this->findMediaCategories($category->getChildren(), $mediaCategories);
            }
        }
        return $mediaCategories;
    }
}