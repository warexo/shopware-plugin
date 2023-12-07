import './sw-desktop-override'
import './sw-page-override'
import './sw-cms-detail-override'

import './sw-cms/blocks/commerce/product-options'
import './sw-cms/elements/product-options'

import deDE from './sw-cms/snippet/de-DE.json';
import enGB from './sw-cms/snippet/en-GB.json';

Shopware.Locale.extend('de-DE', deDE);
Shopware.Locale.extend('en-GB', enGB);