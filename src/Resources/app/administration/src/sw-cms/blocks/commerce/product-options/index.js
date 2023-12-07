import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'product-options',
    label: 'sw-cms.blocks.commerce.product-options.label',
    category: 'commerce',
    component: 'sw-cms-block-product-options',
    previewComponent: 'sw-cms-preview-product-options',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed',
    },
    slots: {
        content: 'product-options',
    },
});