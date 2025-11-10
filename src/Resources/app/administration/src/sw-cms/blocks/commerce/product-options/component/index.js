import template from './sw-cms-block-product-options.html.twig';
import './sw-cms-block-product-options.scss';

const { Component, State, Store } = Shopware;

/**
 * @private since v6.5.0
 * @package content
 */
Component.register('sw-cms-block-product-options', {
    template,

    computed: {
        currentDeviceView() {
            return Store.get('cmsPage').currentCmsDeviceView;
        },

        currentDeviceViewClass() {
            if (this.currentDeviceView) {
                return `is--${this.currentDeviceView}`;
            }

            return null;
        },
    },
});