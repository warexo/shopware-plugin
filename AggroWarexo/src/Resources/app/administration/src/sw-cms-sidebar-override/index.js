Shopware.Component.override('sw-cms-sidebar', {
    computed: {
        showDefaultLayoutSelection() {
            if (this.page.type === 'product_detail') {
                return true;
            }

            return this.$super('showDefaultLayoutSelection');
        }
    }
});