Shopware.Component.override('sw-cms-detail', {
    methods: {
        checkRequiredSlotConfigField(slot, block) {
            if (this.page.type === 'product_detail' && (slot.type === 'product-options')) {
                return [];
            } else {
                return this.$super('checkRequiredSlotConfigField', slot, block);
            }
        }
    }
});
