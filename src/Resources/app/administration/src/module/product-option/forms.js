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
                        type: 'textarea',
                        config: {}
                    }
                ]
            }
        ]
    },
    {
        title: 'warexo.product-option.detail.options.title',
        ident: 'options',
        cards: [
            {
                title: 'warexo.product-option.detail.options.cards.options.title',
                ident: 'options',
                grid: {
                    ref: 'productOptionValues',
                    columns: [
                        {
                            property: 'name',
                            label: 'warexo.product-option.detail.options.cards.options.columnName',
                            inlineEdit: 'string',
                            primary: true,
                        },
                        {
                            property: 'colorHexCode',
                            label: 'warexo.product-option.detail.options.cards.options.columnColor',
                        },
                        {
                            property: 'position',
                            label: 'warexo.product-option.detail.options.cards.options.columnPosition',
                            inlineEdit: 'number',
                        },
                    ],
                    fields: [
                        {
                            ref: 'name',
                            config: {
                                label: 'warexo.product-option.detail.options.cards.options.name.label',
                                required: true,
                                validation: 'required'
                            }
                        },
                        {
                            ref: 'position',
                            type: 'int',
                            config: {
                                label: 'warexo.product-option.detail.options.cards.options.position.label',
                            }
                        },
                        {
                            ref: 'colorHexCode',
                            type: 'colorpicker',
                            config: {
                                zIndex: 1000,
                                label: 'warexo.product-option.detail.options.cards.options.color.label'
                            }
                        },
                        {
                            ref: 'mediaId',
                            config: {
                                componentName: 'sw-media-field',
                                label: 'warexo.product-option.detail.options.cards.options.media.label'
                            }
                        },
                        {
                            ref: 'description',
                            type: 'textarea',
                            config: {
                                label: 'warexo.product-option.detail.options.cards.options.description.label'
                            }
                        }
                    ]
                }
            }
        ]
    }
];