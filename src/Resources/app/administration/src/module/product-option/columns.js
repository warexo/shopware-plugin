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
        property: 'position',
        allowResize: true,
        label: 'warexo.product-option.list.columnActive',
        inlineEdit: 'int'
    }
]