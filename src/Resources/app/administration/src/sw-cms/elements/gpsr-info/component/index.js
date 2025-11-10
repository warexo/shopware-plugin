import template from './sw-cms-el-gpsr-info.html.twig';
import './sw-cms-el-gpsr-info.scss';

const { Component, Mixin } = Shopware;

Component.register('sw-cms-el-gpsr-info', {
    template,

    created() {
        this.createdComponent();
    },

    mixins: [
        Mixin.getByName('cms-element')
    ],

    methods: {
        createdComponent() {
            //this.$set(this.element, 'locked', true);
            this.initElementConfig('gpsr-info');
            /*this.initElementConfig('product-options');
            this.initElementData('product-options');
            this.$set(this.element, 'locked', this.isProductPageType);
            */
        },
    },
});
