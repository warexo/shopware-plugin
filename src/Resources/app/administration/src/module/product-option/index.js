import routes from "./routes";

import deDE from './snippet/de-DE';
import enGB from './snippet/en-GB';

Shopware.Module.register('warexo-product-option', {
    type: 'plugin',
    name: 'Product Options',
    title: 'warexo.product-option.mainMenuItemGeneral',
    description: 'warexo.product-option.descriptionTextModule',
    color: '#00af64',
    icon: 'regular-shopping-bag',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB
    },

    routes,

    navigation: [{
        label: 'warexo.product-option.mainMenuItemGeneral',
        color: '#00af64',
        path: 'warexo.product.option.list',
        icon: 'regular-shopping-bag',
        parent: 'sw-catalogue',
        position: 100
    }]
});