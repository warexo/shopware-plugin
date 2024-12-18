import './component';
import './preview';

/**
 * @private since v6.5.0
 * @package content
 */
Shopware.Service('cmsService').registerCmsElement({
    name: 'gpsr-info',
    label: 'sw-cms.elements.gpsr-info.label',
    component: 'sw-cms-el-gpsr-info',
    previewComponent: 'sw-cms-el-preview-gpsr-info',
    disabledConfigInfoTextKey: 'sw-cms.elements.gpsr-info.infoText.tooltipSettingDisabled',
    defaultConfig: {
        alignment: {
            source: 'static',
            value: null,
        },
    },
});