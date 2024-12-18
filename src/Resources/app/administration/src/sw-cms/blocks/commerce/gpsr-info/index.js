import './component';
import './preview';

Shopware.Service('cmsService').registerCmsBlock({
    name: 'gpsr-info',
    label: 'sw-cms.blocks.commerce.gpsr-info.label',
    category: 'commerce',
    component: 'sw-cms-block-gpsr-info',
    previewComponent: 'sw-cms-preview-gpsr-info',
    defaultConfig: {
        marginBottom: '20px',
        marginTop: '20px',
        marginLeft: '20px',
        marginRight: '20px',
        sizingMode: 'boxed',
    },
    slots: {
        content: 'gpsr-info',
    },
});