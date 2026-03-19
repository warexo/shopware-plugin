import template from './sw-order-line-items-grid.html.twig';

Shopware.Component.override('sw-order-line-items-grid', {
    template,

    methods: {
        warexoIsDecimalQuantityItem(item) {
            return Boolean(item?.payload?.warexoIsDecimalQuantity);
        },

        warexoGetDisplayQuantity(item) {
            if (!this.warexoIsDecimalQuantityItem(item)) {
                return `${item.quantity}`;
            }

            const decimalQuantity = Number(item?.payload?.warexoDecimalQuantity);
            if (Number.isFinite(decimalQuantity)) {
                return new Intl.NumberFormat(this.$i18n.locale, {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 3,
                }).format(decimalQuantity);
            }

            return `${item.quantity}`;
        },
    },
});