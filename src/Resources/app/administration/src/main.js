import './sw-cms-detail-override'
import './sw-property-detail-base-override'

import './sw-cms/blocks/commerce/product-options'
import './sw-cms/elements/product-options'

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';

Shopware.Locale.extend('de-DE', deDE);
Shopware.Locale.extend('en-GB', enGB);