import template from './sw-promotion-v2-individual-codes-behavior.html.twig';

Shopware.Component.override('sw-promotion-v2-individual-codes-behavior', {
    template,

    data() {
        return {
            warexoRestValueEditItem: null,
            warexoRestValueDraft: null,
            warexoRestValueSaving: false,
        };
    },

    computed: {
        codeColumns() {
            const columns = this.$super('codeColumns');
            const createdAtIndex = columns.findIndex((column) => column.property === 'createdAt');

            columns.splice(createdAtIndex === -1 ? columns.length : createdAtIndex, 0, {
                property: 'payload.restValue',
                label: this.$t('warexo.promotion.individualCodes.restValue'),
                sortable: false,
            });

            return columns;
        },
    },

    methods: {
        warexoIsRedeemed(item) {
            return Boolean(item?.payload) && !this.warexoHasRestValue(item);
        },

        warexoHasRestValue(item) {
            const value = item?.payload?.restValue;
            const restValue = Number(value);

            return value !== null && value !== undefined && value !== '' && Number.isFinite(restValue) && restValue >= 0;
        },

        warexoGetRestValue(item) {
            return this.warexoHasRestValue(item) ? Number(item.payload.restValue) : null;
        },

        warexoUpdateRestValue(item, value) {
            const payload = item.payload && typeof item.payload === 'object' ? { ...item.payload } : {};
            const restValue = Number(value);

            if (value === null || value === '' || !Number.isFinite(restValue) || restValue < 0) {
                delete payload.restValue;
            } else {
                payload.restValue = restValue;
            }
            item.payload = Object.keys(payload).length > 0 ? payload : null;
        },

        warexoOpenRestValueModal(item) {
            this.warexoRestValueEditItem = item;
            this.warexoRestValueDraft = this.warexoGetRestValue(item);
        },

        warexoCloseRestValueModal() {
            this.warexoRestValueEditItem = null;
            this.warexoRestValueDraft = null;
        },

        async warexoSaveRestValue() {
            if (!this.warexoRestValueEditItem) {
                return;
            }

            this.warexoRestValueSaving = true;
            this.warexoUpdateRestValue(this.warexoRestValueEditItem, this.warexoRestValueDraft);

            try {
                await this.$refs.individualCodesGrid.save(this.warexoRestValueEditItem);
                this.warexoCloseRestValueModal();
            } catch {
                await this.$refs.individualCodesGrid.revert();
                this.createNotificationError({
                    message: this.$t('warexo.promotion.individualCodes.saveError'),
                });
                this.warexoCloseRestValueModal();
            } finally {
                this.warexoRestValueSaving = false;
            }
        },
    },
});
