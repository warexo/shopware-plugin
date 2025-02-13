export default [
    {
        property: 'name',
        dataIndex: 'name',
        allowResize: true,
        routerLink: 'warexo.product.option.detail',
        label: 'warexo.product-option.list.columnName',
        inlineEdit: 'string',
        primary: true,
    },
    {
        property: 'ident',
        allowResize: true,
        label: 'warexo.product-option.list.columnIdent',
        inlineEdit: 'string'
    },
    {
        property: 'position',
        allowResize: true,
        label: 'warexo.product-option.list.columnPosition',
        inlineEdit: 'int'
    }
]