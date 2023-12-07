import './component';
import './config';
import './preview';

const Criteria = Shopware.Data.Criteria;
const criteria = new Criteria(1, 25);
criteria.addAssociation('deliveryTime');

/**
 * @private since v6.5.0
 * @package content
 */
Shopware.Service('cmsService').registerCmsElement({
    name: 'product-options',
    label: 'sw-cms.elements.product-options.label',
    component: 'sw-cms-el-product-options',
    configComponent: 'sw-cms-el-config-product-options',
    previewComponent: 'sw-cms-el-preview-product-options',
    disabledConfigInfoTextKey: 'sw-cms.elements.product-options.infoText.tooltipSettingDisabled',
    defaultConfig: {
        product: {
            source: 'static',
            value: null,
            required: true,
            entity: {
                name: 'product',
                criteria: criteria,
            },
        },
        alignment: {
            source: 'static',
            value: null,
        },
    },
    defaultData: {
        product: {
            name: 'Lorem Ipsum dolor',
            productNumber: 'XXXXXX',
            minPurchase: 1,
            deliveryTime: {
                name: '1-3 days',
            },
            price: [
                { gross: 0.00 },
            ],
        },
    },
    collect: Shopware.Service('cmsService').getCollectFunction(),
});