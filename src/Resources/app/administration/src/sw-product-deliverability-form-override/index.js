import template from './sw-product-deliverability-form.html.twig';

Shopware.Component.override('sw-product-deliverability-form', {
    template,

    inject: [
        'repositoryFactory',
        'systemConfigApiService',
    ],

    data() {
        return {
            warexoDecimalStockEnabled: false,
        };
    },

    computed: {
        warexoProductExtensionRepository() {
            return this.repositoryFactory.create('warexo_product_extension');
        },

        warexoExtension() {
            if (!this.product) {
                return null;
            }

            if (typeof this.product.getExtension === 'function') {
                return this.product.getExtension('warexoExtension') ?? null;
            }

            return this.product.extensions?.warexoExtension ?? null;
        },

        hasWarexoExtension() {
            return this.warexoExtension !== null;
        },
    },

    watch: {
        product: {
            handler() {
                this.ensureWarexoExtension();
            },
            immediate: true,
        },
    },

    created() {
        this.loadWarexoDecimalStockConfig();
    },

    methods: {
        async loadWarexoDecimalStockConfig() {
            const values = await this.systemConfigApiService.getValues('AggroWarexoPlugin.config');
            this.warexoDecimalStockEnabled = values['AggroWarexoPlugin.config.decimalstock'] === true;
        },

        ensureWarexoExtension() {
            if (!this.product) {
                return;
            }

            const existingExtension = this.warexoExtension;
            if (existingExtension) {
                if (!existingExtension.productId && this.product.id) {
                    existingExtension.productId = this.product.id;
                }

                return;
            }

            const warexoExtension = this.warexoProductExtensionRepository.create(Shopware.Context.api);
            warexoExtension.productId = this.product.id;
            warexoExtension.position = 0;
            warexoExtension.stock = null;
            warexoExtension.minPurchase = null;
            warexoExtension.maxPurchase = null;
            warexoExtension.purchaseSteps = null;

            if (typeof this.product.addExtension === 'function') {
                this.product.addExtension('warexoExtension', warexoExtension);

                return;
            }

            if (!this.product.extensions || typeof this.product.extensions !== 'object') {
                this.product.extensions = {};
            }

            this.product.extensions.warexoExtension = warexoExtension;
        },
    },
});