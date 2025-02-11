export default [
    {
        title: 'warexo.product-option.detail.basics.title',
        ident: 'basics',
        cards: [
            {
                title: 'warexo.product-option.detail.basics.cards.general.title',
                ident: 'basic-info',
                columns: '1fr 1fr',
                fields: [
                    {
                        ref: 'name',
                        config: {
                            label: 'warexo.product-option.detail.basics.cards.general.name.label',
                            placeholder: 'warexo.product-option.detail.basics.cards.general.name.placeholder',
                            required: true
                        }
                    },
                    {
                        ref: 'ident',
                        config: {
                            label: 'warexo.product-option.detail.basics.cards.general.ident.label',
                            placeholder: 'warexo.product-option.detail.basics.cards.general.ident.placeholder',
                        }
                    },
                    {
                        ref: 'position',
                        type: 'int',
                        config: {
                            label: 'warexo.product-option.detail.basics.cards.general.position.label',
                        }
                    },
                    {
                        ref: 'displayType',
                        type: 'single-select',
                        required: true,
                        config: {
                            label: 'warexo.product-option.detail.basics.cards.general.displayType.label',
                            options: [
                                { value: 'text', 'label': 'warexo.product-option.detail.basics.cards.general.displayType.fixed' },
                                { value: 'select', 'label': 'warexo.product-option.detail.basics.cards.general.displayType.select' },
                                { value: 'color', 'label': 'warexo.product-option.detail.basics.cards.general.displayType.color' }
                            ]
                        }
                    }
                ]
            },
            {
                title: 'warexo.product-option.detail.basics.cards.description.title',
                ident: 'description',
                columns: '1fr',
                fields: [
                    {
                        ref: 'description',
                        config: {
                            componentName: 'sw-text-editor',
                            type: 'textarea',
                            sanitizeInput: true,
                            sanitizeFieldName: 'warexo_product_option_translation.description'
                        }
                    }
                ]
            }
        ]
    }
];
/*
export default [
    {
        title: 'aggro-surcharge.detail.basics.title',
        ident: 'basics',
        cards: [
            {
                title: 'aggro-surcharge.detail.basics.cards.general.title',
                ident: 'basic-info',
                columns: '1fr 1fr',
                fields: [
                    {
                        ref: 'active',
                        type: 'bool',
                        config: {
                            label: 'aggro-surcharge.detail.basics.cards.general.active.label',
                        }
                    },
                    {
                        ref: 'removable',
                        type: 'bool',
                        config: {
                            label: 'aggro-surcharge.detail.basics.cards.general.removable.label',
                        }
                    },
                    {
                        ref: 'name',
                        config: {
                            label: 'aggro-surcharge.detail.basics.cards.general.name.label',
                            placeholder: 'aggro-surcharge.detail.basics.cards.general.name.placeholder',
                            required: true
                        }
                    },
                    {
                        ref: 'priority',
                        type: 'int',
                        config: {
                            label: 'aggro-surcharge.detail.basics.cards.general.priority.label',
                            helpText: 'aggro-surcharge.detail.basics.cards.general.priority.helpText',
                            required: true
                        }
                    }
                ]
            },
            {
                title: 'aggro-surcharge.detail.basics.cards.rules.title',
                ident: 'rules',
                columns: '1fr 1fr',
                fields: [
                    {
                        ref: 'ruleId',
                        type: 'single-entity-id-select',
                        config: {
                            label: 'aggro-surcharge.detail.basics.cards.rules.rule.label',
                            entity: 'rule'
                        }
                    },
                    {
                        ref: 'salesChannels',
                        config: {
                            componentName: 'sw-entity-multi-select',
                            label: 'aggro-surcharge.detail.basics.cards.rules.salesChannels.label',
                            entityName: 'sales_channel'
                        }
                    }
                ]
            }
        ]
    },
    {
        title: 'aggro-surcharge.detail.product.title',
        ident: 'product',
        cards: [
            {
                title: 'aggro-surcharge.detail.product.cards.product-selection.title',
                ident: 'product-selection',
                columns: '2fr 1fr',
                fields: [
                    {
                        ref: 'productId',
                        type: 'single-entity-id-select',
                        config: {
                            label: 'aggro-surcharge.detail.product.cards.product-selection.product.label',
                            entity: 'product',
                            required: true
                        }
                    },
                    {
                        ref: 'free',
                        type: 'bool',
                        config: {
                            label: 'aggro-surcharge.detail.product.cards.product-selection.free.label',
                        }
                    },
                    {
                        ref: 'shippingCostAware',
                        type: 'bool',
                        config: {
                            label: 'aggro-surcharge.detail.product.cards.product-selection.shippingCostAware.label',
                        }
                    }
                ]
            }
        ]
    },
    {
        title: 'aggro-surcharge.detail.quantities.title',
        ident: 'quantities',
        cards: [
            {
                title: 'aggro-surcharge.detail.quantities.cards.quantities.title',
                ident: 'quantities',
                columns: '1fr 1fr',
                fields: [
                    {
                        ref: 'quantityMode',
                        type: 'single-select',
                        required: true,
                        config: {
                            label: 'aggro-surcharge.detail.quantities.cards.quantities.quantityMode.label',
                            options: [
                                { value: 'fixed', 'label': 'aggro-surcharge.detail.quantities.cards.quantities.quantityMode.fixed' },
                                { value: 'position', 'label': 'aggro-surcharge.detail.quantities.cards.quantities.quantityMode.position' },
                                { value: 'quantity', 'label': 'aggro-surcharge.detail.quantities.cards.quantities.quantityMode.quantity' },
                                { value: 'unit', 'label': 'aggro-surcharge.detail.quantities.cards.quantities.quantityMode.unit' },
                                { value: 'warexo_material', 'label': 'aggro-surcharge.detail.quantities.cards.quantities.quantityMode.warexo_material' },
                            ]
                        }
                    },
                    {
                        ref: 'fixQuantity',
                        type: 'int',
                        config: {
                            label: 'aggro-surcharge.detail.quantities.cards.quantities.fixQuantity.label',
                        },
                        condition(entity) {
                            return entity.quantityMode === 'fixed';
                        }
                    },
                    {
                        ref: 'products',
                        config: {
                            componentName: 'sw-entity-multi-select',
                            label: 'aggro-surcharge.detail.quantities.cards.quantities.products.label',
                            entityName: 'product'
                        },
                        condition(entity) {
                            return entity.quantityMode === 'position' ||
                                entity.quantityMode === 'quantity' ||
                                entity.quantityMode === 'unit' ||
                                entity.quantityMode === 'warexo_material';
                        }
                    },
                    {
                        ref: 'productStreams',
                        config: {
                            componentName: 'sw-entity-multi-select',
                            label: 'aggro-surcharge.detail.quantities.cards.quantities.productStreams.label',
                            entityName: 'product_stream'
                        },
                        condition(entity) {
                            return entity.quantityMode === 'position' ||
                                entity.quantityMode === 'quantity' ||
                                entity.quantityMode === 'unit' ||
                                entity.quantityMode === 'warexo_material';
                        }
                    },
                    {
                        ref: 'tags',
                        config: {
                            componentName: 'sw-entity-multi-select',
                            label: 'aggro-surcharge.detail.quantities.cards.quantities.tags.label',
                            entityName: 'tag'
                        },
                        condition(entity) {
                            return entity.quantityMode === 'position' ||
                                entity.quantityMode === 'quantity' ||
                                entity.quantityMode === 'unit' ||
                                entity.quantityMode === 'warexo_material';
                        }
                    }
                ]
            }
        ]
    }
];
 */