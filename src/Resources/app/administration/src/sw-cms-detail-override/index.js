Shopware.Component.override('sw-cms-detail', {
    methods: {
        getSlotValidations() {
            const uniqueSlotCount = {};
            const requiredMissingSlotConfigs = [];
            return {
                requiredMissingSlotConfigs,
                uniqueSlotCount,
            };
        },

        checkRequiredSlotConfigField(slot, block) {
            if (this.page.type === 'product_detail' && (slot.type === 'product-options')) {
                return [];
            } else {
                return this.$super('checkRequiredSlotConfigField', slot, block);
            }
        },

        isProductPageElement(slot) {
            if (slot.type === 'product-options') {
                return true;
            }
            return this.$super('isProductPageElement', slot);
        }

    }
});
