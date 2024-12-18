import template from './sw-cms-block-gpsr-info.html.twig';
import './sw-cms-block-gpsr-info.scss';

const { Component } = Shopware;

/**
 * @private since v6.5.0
 * @package content
 */
Component.register('sw-cms-block-gpsr-info', {
    template,
});