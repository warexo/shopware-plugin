import './sw-cms-detail-override'
import './sw-property-detail-base-override'
import './sw-product-detail-override'
import './sw-product-category-form-override'

import './sw-cms/blocks/commerce/product-options'
import './sw-cms/blocks/commerce/gpsr-info'
import './sw-cms/elements/product-options'
import './sw-cms/elements/gpsr-info'

import './module/product-option'

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Shopware.Locale.extend('de-DE', deDE);
Shopware.Locale.extend('en-GB', enGB);