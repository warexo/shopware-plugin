!function(e){var t={};function n(o){if(t[o])return t[o].exports;var i=t[o]={i:o,l:!1,exports:{}};return e[o].call(i.exports,i,i.exports,n),i.l=!0,i.exports}n.m=e,n.c=t,n.d=function(e,t,o){n.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:o})},n.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},n.t=function(e,t){if(1&t&&(e=n(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var o=Object.create(null);if(n.r(o),Object.defineProperty(o,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var i in e)n.d(o,i,function(t){return e[t]}.bind(null,i));return o},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p=(window.__sw__.assetPath + '/bundles/aggrowarexoplugin/'),n(n.s="4vU9")}({"+lpJ":function(e,t){Shopware.Component.override("sw-cms-detail",{methods:{getSlotValidations:function(){return{requiredMissingSlotConfigs:[],uniqueSlotCount:{}}},checkRequiredSlotConfigField:function(e,t){return"product_detail"===this.page.type&&"product-options"===e.type?[]:this.$super("checkRequiredSlotConfigField",e,t)},isProductPageElement:function(e){return"product-options"===e.type||this.$super("isProductPageElement",e)}}})},"41Pl":function(e){e.exports=JSON.parse('{"sw-cms":{"blocks":{"commerce":{"product-options":{"label":"Warexo Produkt Optionen"}}},"elements":{"product-options":{"label":"Warexo Produkt Optionen"}}}}')},"4vU9":function(e,t,n){"use strict";n.r(t);n("Ct6t"),n("mSkR");Shopware.Component.override("sw-page",{computed:{pageClasses:function(){return Object.assign({},this.$super("pageClasses"),{"is--framed":this.isFramed})},isFramed:function(){return 1==this.$route.query.framed}}});n("+lpJ"),n("vl7m");var o=Shopware,i=o.Component,r=o.State;i.register("sw-cms-block-product-options",{template:'\n{% block sw_cms_block_product_options %}\n    <div\n            class="sw-cms-block-product-options"\n            :class="currentDeviceViewClass"\n    >\n        <slot name="content">\n            \n            {% block sw_cms_block_product_options_slot_content %}{% endblock %}\n        </slot>\n    </div>\n{% endblock %}',computed:{currentDeviceView:function(){return r.get("cmsPageState").currentCmsDeviceView},currentDeviceViewClass:function(){return this.currentDeviceView?"is--".concat(this.currentDeviceView):null}}});n("HM27");Shopware.Component.register("sw-cms-preview-product-options",{template:'{% block sw_cms_block_product_options_preview %}\n    <div class="sw-cms-preview-product-options">\n        <sw-select-field label="Option 1" />\n        <sw-select-field label="Option 2" />\n    </div>\n{% endblock %}'}),Shopware.Service("cmsService").registerCmsBlock({name:"product-options",label:"sw-cms.blocks.commerce.product-options.label",category:"commerce",component:"sw-cms-block-product-options",previewComponent:"sw-cms-preview-product-options",defaultConfig:{marginBottom:"20px",marginTop:"20px",marginLeft:"20px",marginRight:"20px",sizingMode:"boxed"},slots:{content:"product-options"}});n("9po/");var s=Shopware,c=s.Component,l=s.Mixin;c.register("sw-cms-el-product-options",{template:'{% block sw_cms_el_product_options %}\n<div\n    class="sw-cms-el-product-options"\n    :style="alignStyle"\n>\n    <sw-select-field label="Option 1" />\n    <sw-select-field label="Option 2" />\n</div>\n{% endblock %}\n',mixins:[l.getByName("cms-element"),l.getByName("placeholder")],computed:{product:function(){var e,t,n,o;return this.currentDemoEntity?this.currentDemoEntity:null!==(e=this.element.data)&&void 0!==e&&e.product?null!==(t=null===(n=this.element)||void 0===n||null===(o=n.data)||void 0===o?void 0:o.product)&&void 0!==t?t:null:{name:"Lorem Ipsum dolor",productNumber:"XXXXXX",minPurchase:1,deliveryTime:{name:"1-3 days"},price:[{gross:0}]}},pageType:function(){var e,t,n;return null!==(e=null===(t=this.cmsPageState)||void 0===t||null===(n=t.currentPage)||void 0===n?void 0:n.type)&&void 0!==e?e:""},isProductPageType:function(){return"product_detail"===this.pageType},alignStyle:function(){var e,t;return null!==(e=this.element.config)&&void 0!==e&&null!==(t=e.alignment)&&void 0!==t&&t.value?"justify-content: ".concat(this.element.config.alignment.value,";"):null},currentDemoEntity:function(){return"product"===this.cmsPageState.currentMappingEntity?this.cmsPageState.currentDemoEntity:null}},watch:{pageType:function(e){this.$set(this.element,"locked","product_detail"===e)}},created:function(){this.createdComponent()},methods:{createdComponent:function(){this.initElementConfig("product-options"),this.initElementData("product-options"),this.$set(this.element,"locked",this.isProductPageType)}}});n("RfD0");function a(e){return(a="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function u(e,t){var n=Object.keys(e);if(Object.getOwnPropertySymbols){var o=Object.getOwnPropertySymbols(e);t&&(o=o.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),n.push.apply(n,o)}return n}function p(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?u(Object(n),!0).forEach((function(t){d(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):u(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function d(e,t,n){return(t=function(e){var t=function(e,t){if("object"!==a(e)||null===e)return e;var n=e[Symbol.toPrimitive];if(void 0!==n){var o=n.call(e,t||"default");if("object"!==a(o))return o;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===t?String:Number)(e)}(e,"string");return"symbol"===a(t)?t:String(t)}(t))in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}var m=Shopware,f=m.Component,g=m.Mixin,v=Shopware.Data.Criteria;f.register("sw-cms-el-config-product-options",{template:'{% block sw_cms_element_product_options_config %}\n<div class="sw-cms-el-config-product-options">\n\n    {% block sw_cms_element_product_options_config_tabs %}\n    <sw-tabs\n        position-identifier="sw-cms-element-config-product-options"\n        class="sw-cms-el-config-product-options__tabs"\n        default-item="content"\n    >\n        <template #default="{ active }">\n\n            {% block sw_cms_element_product_options_config_tab_content %}\n            <sw-tabs-item\n                name="content"\n                :title="$tc(\'sw-cms.elements.general.config.tab.content\')"\n                :active-tab="active"\n            >\n                {{ $tc(\'sw-cms.elements.general.config.tab.content\') }}\n            </sw-tabs-item>\n            {% endblock %}\n\n            {% block sw_cms_element_product_options_config_tab_option %}\n            <sw-tabs-item\n                name="options"\n                :title="$tc(\'sw-cms.elements.general.config.tab.options\')"\n                :active-tab="active"\n            >\n                {{ $tc(\'sw-cms.elements.general.config.tab.options\') }}\n            </sw-tabs-item>\n            {% endblock %}\n        </template>\n\n        <template #content="{ active }">\n            <div\n                v-if="active === \'content\'"\n                class="sw-cms-el-config-product-options__tab-content"\n            >\n                {% block sw_cms_element_product_options_config_content_warning %}\n                <sw-alert\n                    v-if="isProductPage"\n                    class="sw-cms-el-config-product-options__warning"\n                    variant="info"\n                >\n                    {{ $tc(\'sw-cms.elements.configurator.infoText.tooltipSettingDisabled\') }}\n                </sw-alert>\n                {% endblock %}\n\n                {% block sw_cms_element_product_options_config_product_select %}\n                <sw-entity-single-select\n                    v-if="!isProductPage"\n                    ref="cmsProductSelection"\n                    v-model="element.config.product.value"\n                    entity="product"\n                    :label="$tc(\'sw-cms.elements.configurator.config.label.selection\')"\n                    :placeholder="$tc(\'sw-cms.elements.configurator.config.placeholder.selection\')"\n                    :criteria="productCriteria"\n                    :context="productSelectContext"\n                    show-clearable-button\n                    @change="onProductChange"\n                >\n\n                    {% block sw_cms_element_product_options_config_product_variant_label %}\n                    <template #selection-label-property="{ item }">\n                        <sw-product-variant-info :variations="item.variation">\n                            {{ item.translated.name || item.name }}\n                        </sw-product-variant-info>\n                    </template>\n                    {% endblock %}\n\n                    {% block sw_cms_element_product_options_config_product_select_result_item %}\n                    <template #result-item="{ item, index }">\n                        <li\n                            is="sw-select-result"\n                            v-bind="{ item, index }"\n                        >\n\n                            {% block sw_entity_single_select_base_results_list_result_label %}\n                            <span class="sw-select-result__result-item-text">\n                                <sw-product-variant-info :variations="item.variation">\n                                    {{ item.translated.name || item.name }}\n                                </sw-product-variant-info>\n                            </span>\n                            {% endblock %}\n\n                        </li>\n                    </template>\n                    {% endblock %}\n\n                </sw-entity-single-select>\n                {% endblock %}\n            </div>\n\n            <div\n                v-if="active === \'options\'"\n                class="sw-cms-el-config-product-options__tab-options"\n            >\n\n                {% block sw_cms_element_product_options_config_options %}\n                <sw-select-field\n                    v-model="element.config.alignment.value"\n                    class="sw-cms-el-config-product-options__alignment"\n                    :label="$tc(\'sw-cms.elements.general.config.label.alignment\')"\n                    :placeholder="$tc(\'sw-cms.elements.general.config.label.alignment\')"\n                >\n\n                    {% block sw_cms_element_product_box_config_alignment_options %}\n                    <option value="flex-start">\n                        {{ $tc(\'sw-cms.elements.general.config.label.verticalAlignTop\') }}\n                    </option>\n                    <option value="center">\n                        {{ $tc(\'sw-cms.elements.general.config.label.verticalAlignCenter\') }}\n                    </option>\n                    <option value="flex-end">\n                        {{ $tc(\'sw-cms.elements.general.config.label.verticalAlignBottom\') }}\n                    </option>\n                    {% endblock %}\n\n                </sw-select-field>\n                {% endblock %}\n\n            </div>\n        </template>\n    </sw-tabs>\n    {% endblock %}\n\n</div>\n{% endblock %}\n',inject:["repositoryFactory"],mixins:[g.getByName("cms-element")],computed:{productRepository:function(){return this.repositoryFactory.create("product")},productSelectContext:function(){return p(p({},Shopware.Context.api),{},{inheritance:!0})},productCriteria:function(){var e=new v(1,25);return e.addAssociation("options.group"),e},selectedProductCriteria:function(){var e=new v(1,25);return e.addAssociation("deliveryTime"),e},isProductPage:function(){var e,t;return"product_detail"===(null===(e=this.cmsPageState)||void 0===e||null===(t=e.currentPage)||void 0===t?void 0:t.type)}},created:function(){this.createdComponent()},methods:{createdComponent:function(){this.initElementConfig("product-options")},onProductChange:function(e){var t=this;e?this.productRepository.get(e,this.productSelectContext,this.selectedProductCriteria).then((function(n){t.element.config.product.value=e,t.$set(t.element.data,"productId",e),t.$set(t.element.data,"product",n)})):(this.element.config.product.value=null,this.$set(this.element.data,"productId",null),this.$set(this.element.data,"product",null)),this.$emit("element-update",this.element)}}});n("R0la");Shopware.Component.register("sw-cms-el-preview-product-options",{template:'{% block sw_cms_element_product_options_preview %}\n<div class="sw-cms-el-preview-product-options">\n    <sw-select-field label="Option 1" />\n</div>\n{% endblock %}\n'});var b=new(0,Shopware.Data.Criteria)(1,25);b.addAssociation("deliveryTime"),Shopware.Service("cmsService").registerCmsElement({name:"product-options",label:"sw-cms.elements.product-options.label",component:"sw-cms-el-product-options",configComponent:"sw-cms-el-config-product-options",previewComponent:"sw-cms-el-preview-product-options",disabledConfigInfoTextKey:"sw-cms.elements.product-options.infoText.tooltipSettingDisabled",defaultConfig:{product:{source:"static",value:null,required:!0,entity:{name:"product",criteria:b}},alignment:{source:"static",value:null}},defaultData:{product:{name:"Lorem Ipsum dolor",productNumber:"XXXXXX",minPurchase:1,deliveryTime:{name:"1-3 days"},price:[{gross:0}]}},collect:Shopware.Service("cmsService").getCollectFunction()});var _=n("65tJ"),w=n("41Pl");Shopware.Locale.extend("de-DE",_),Shopware.Locale.extend("en-GB",w)},"65tJ":function(e){e.exports=JSON.parse('{"sw-cms":{"blocks":{"commerce":{"product-options":{"label":"Warexo Produkt Optionen"}}},"elements":{"product-options":{"label":"Warexo Produkt Optionen"}}}}')},"6IEQ":function(e,t,n){},"9po/":function(e,t,n){var o=n("Lpym");o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.i,o,""]]),o.locals&&(e.exports=o.locals);(0,n("ydqr").default)("749644f8",o,!0,{})},Ct6t:function(e,t){Shopware.Component.override("sw-desktop",{methods:{checkRouteSettings:function(){this.$super("checkRouteSettings"),this.isFramed&&(this.noNavigation=!0)}},computed:{isFramed:function(){return 1==this.$route.query.framed}}})},HM27:function(e,t,n){var o=n("6IEQ");o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.i,o,""]]),o.locals&&(e.exports=o.locals);(0,n("ydqr").default)("c0872e96",o,!0,{})},Lpym:function(e,t,n){},OGlF:function(e,t,n){},R0la:function(e,t,n){var o=n("YVgK");o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.i,o,""]]),o.locals&&(e.exports=o.locals);(0,n("ydqr").default)("2097c5d2",o,!0,{})},RfD0:function(e,t,n){var o=n("ocS7");o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.i,o,""]]),o.locals&&(e.exports=o.locals);(0,n("ydqr").default)("2f904a02",o,!0,{})},YVgK:function(e,t,n){},mSkR:function(e,t,n){var o=n("OGlF");o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.i,o,""]]),o.locals&&(e.exports=o.locals);(0,n("ydqr").default)("7b785fdf",o,!0,{})},ocS7:function(e,t,n){},vl7m:function(e,t,n){var o=n("z32K");o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.i,o,""]]),o.locals&&(e.exports=o.locals);(0,n("ydqr").default)("1e957aa0",o,!0,{})},ydqr:function(e,t,n){"use strict";function o(e,t){for(var n=[],o={},i=0;i<t.length;i++){var r=t[i],s=r[0],c={id:e+":"+i,css:r[1],media:r[2],sourceMap:r[3]};o[s]?o[s].parts.push(c):n.push(o[s]={id:s,parts:[c]})}return n}n.r(t),n.d(t,"default",(function(){return f}));var i="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!i)throw new Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var r={},s=i&&(document.head||document.getElementsByTagName("head")[0]),c=null,l=0,a=!1,u=function(){},p=null,d="data-vue-ssr-id",m="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function f(e,t,n,i){a=n,p=i||{};var s=o(e,t);return g(s),function(t){for(var n=[],i=0;i<s.length;i++){var c=s[i];(l=r[c.id]).refs--,n.push(l)}t?g(s=o(e,t)):s=[];for(i=0;i<n.length;i++){var l;if(0===(l=n[i]).refs){for(var a=0;a<l.parts.length;a++)l.parts[a]();delete r[l.id]}}}}function g(e){for(var t=0;t<e.length;t++){var n=e[t],o=r[n.id];if(o){o.refs++;for(var i=0;i<o.parts.length;i++)o.parts[i](n.parts[i]);for(;i<n.parts.length;i++)o.parts.push(b(n.parts[i]));o.parts.length>n.parts.length&&(o.parts.length=n.parts.length)}else{var s=[];for(i=0;i<n.parts.length;i++)s.push(b(n.parts[i]));r[n.id]={id:n.id,refs:1,parts:s}}}}function v(){var e=document.createElement("style");return e.type="text/css",s.appendChild(e),e}function b(e){var t,n,o=document.querySelector("style["+d+'~="'+e.id+'"]');if(o){if(a)return u;o.parentNode.removeChild(o)}if(m){var i=l++;o=c||(c=v()),t=h.bind(null,o,i,!1),n=h.bind(null,o,i,!0)}else o=v(),t=y.bind(null,o),n=function(){o.parentNode.removeChild(o)};return t(e),function(o){if(o){if(o.css===e.css&&o.media===e.media&&o.sourceMap===e.sourceMap)return;t(e=o)}else n()}}var _,w=(_=[],function(e,t){return _[e]=t,_.filter(Boolean).join("\n")});function h(e,t,n,o){var i=n?"":o.css;if(e.styleSheet)e.styleSheet.cssText=w(t,i);else{var r=document.createTextNode(i),s=e.childNodes;s[t]&&e.removeChild(s[t]),s.length?e.insertBefore(r,s[t]):e.appendChild(r)}}function y(e,t){var n=t.css,o=t.media,i=t.sourceMap;if(o&&e.setAttribute("media",o),p.ssrId&&e.setAttribute(d,t.id),i&&(n+="\n/*# sourceURL="+i.sources[0]+" */",n+="\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(i))))+" */"),e.styleSheet)e.styleSheet.cssText=n;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(n))}}},z32K:function(e,t,n){}});