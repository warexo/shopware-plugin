import columns from './columns'
import forms from "./forms";

const entity = 'warexo_product_option'
const create = 'warexo.product.option.create'
const detail = 'warexo.product.option.detail'
const list = 'warexo.product.option.list'

export default {
    list: {
        component: 'aggro-entity-list',
        path: 'list',
        props: {
            default: {
                entity,
                columns,
                labels: {
                    header: 'warexo.product-option.list.header',
                    add: 'warexo.product-option.list.add',
                },
                links: {
                    create,
                    detail
                }
            }
        },
    },
    create: {
        component: 'aggro-entity-detail',
        path: 'create',
        props: {
            default: {
                entity,
                forms,
                links: {
                    list,
                    detail
                }
            }
        },
        meta: {
            parentPath: list
        }
    },
    detail: {
        component: 'aggro-entity-detail',
        path: 'detail/:id',
        props: {
            default(route) {
                return {
                    entityId: route.params.id,
                    entity,
                    forms,
                    links: {
                        list,
                        detail
                    }
                }
            }
        },
        meta: {
            parentPath: list
        }
    },
};