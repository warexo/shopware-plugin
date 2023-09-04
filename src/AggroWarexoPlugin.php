<?php declare(strict_types=1);

namespace Warexo;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\System\CustomField\Aggregate\CustomFieldSet\CustomFieldSetEntity;
use Shopware\Core\System\CustomField\CustomFieldTypes;

class AggroWarexoPlugin extends Plugin
{

    public function install(Plugin\Context\InstallContext $installContext): void
    {

        if (!$this->getCustomFieldSet($installContext->getContext(), 'warexo_category_custom_fields')) {
            $customFieldSetRepository = $this->container->get('custom_field_set.repository');
            $customFieldSetRepository->create([
                [
                    'name' => 'warexo_category_custom_fields',
                    'config' => [
                        'label' => [
                            'de-DE' => 'Warexo Kategorie Felder',
                            'en-GB' => 'Warexo Category Fields'
                        ]
                    ],
                    'customFields' => [
                        [
                            'name' => 'custom_warexo_icon',
                            'type' => CustomFieldTypes::TEXT,
                            'config' => [
                                'label' => [
                                    'de-DE' => 'Icon',
                                    'en-GB' => 'Icon'
                                ],
                                'componentName' => 'sw-media-field',
                                'customFieldPosition' => 1,
                                'customFieldType' => 'media'
                            ]
                        ]
                    ],
                    'relations' => [
                        [
                            'entityName' => 'category'
                        ]
                    ]
                ]
            ], $installContext->getContext());
        }
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if (!$uninstallContext->keepUserData()) {
            $customFieldSet = $this->getCustomFieldSet($uninstallContext->getContext(), 'warexo_category_custom_fields');
            if ($customFieldSet) {
                $customFieldSetRepository = $this->container->get('custom_field_set.repository');
                $customFieldSetRepository->delete([['id' => $customFieldSet->getId()]], $uninstallContext->getContext());
            }
        }
    }

    private function getCustomFieldSet (Context $context, string $name): ?CustomFieldSetEntity
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        return $customFieldSetRepository->search(
            (new Criteria())->addFilter(new EqualsFilter('name', $name)),
            $context
        )->first();
    }
}