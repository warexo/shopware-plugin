(function(){var e={361:function(){},971:function(){},962:function(){},779:function(){},236:function(){},626:function(){},372:function(){},335:function(){},76:function(){},860:function(){Shopware.Component.override("sw-cms-detail",{methods:{getSlotValidations(){return{requiredMissingSlotConfigs:[],uniqueSlotCount:{}}},checkRequiredSlotConfigField(e,t){return"product_detail"===this.page.type&&"product-options"===e.type?[]:this.$super("checkRequiredSlotConfigField",e,t)},isProductPageElement(e){return"product-options"===e.type||this.$super("isProductPageElement",e)}}})},697:function(e,t,n){var o=n(361);o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.id,o,""]]),o.locals&&(e.exports=o.locals),n(346).Z("3c817999",o,!0,{})},392:function(e,t,n){var o=n(971);o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.id,o,""]]),o.locals&&(e.exports=o.locals),n(346).Z("2a447884",o,!0,{})},464:function(e,t,n){var o=n(962);o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.id,o,""]]),o.locals&&(e.exports=o.locals),n(346).Z("0108afb4",o,!0,{})},536:function(e,t,n){var o=n(779);o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.id,o,""]]),o.locals&&(e.exports=o.locals),n(346).Z("eef2ab5e",o,!0,{})},73:function(e,t,n){var o=n(236);o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.id,o,""]]),o.locals&&(e.exports=o.locals),n(346).Z("120f7bad",o,!0,{})},574:function(e,t,n){var o=n(626);o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.id,o,""]]),o.locals&&(e.exports=o.locals),n(346).Z("22d8753c",o,!0,{})},726:function(e,t,n){var o=n(372);o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.id,o,""]]),o.locals&&(e.exports=o.locals),n(346).Z("239c4200",o,!0,{})},763:function(e,t,n){var o=n(335);o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.id,o,""]]),o.locals&&(e.exports=o.locals),n(346).Z("ad85fd62",o,!0,{})},808:function(e,t,n){var o=n(76);o.__esModule&&(o=o.default),"string"==typeof o&&(o=[[e.id,o,""]]),o.locals&&(e.exports=o.locals),n(346).Z("64d65475",o,!0,{})},346:function(e,t,n){"use strict";function o(e,t){for(var n=[],o={},i=0;i<t.length;i++){var r=t[i],s=r[0],l={id:e+":"+i,css:r[1],media:r[2],sourceMap:r[3]};o[s]?o[s].parts.push(l):n.push(o[s]={id:s,parts:[l]})}return n}n.d(t,{Z:function(){return g}});var i="undefined"!=typeof document;if("undefined"!=typeof DEBUG&&DEBUG&&!i)throw Error("vue-style-loader cannot be used in a non-browser environment. Use { target: 'node' } in your Webpack config to indicate a server-rendering environment.");var r={},s=i&&(document.head||document.getElementsByTagName("head")[0]),l=null,a=0,c=!1,p=function(){},d=null,u="data-vue-ssr-id",m="undefined"!=typeof navigator&&/msie [6-9]\b/.test(navigator.userAgent.toLowerCase());function g(e,t,n,i){c=n,d=i||{};var s=o(e,t);return f(s),function(t){for(var n=[],i=0;i<s.length;i++){var l=r[s[i].id];l.refs--,n.push(l)}t?f(s=o(e,t)):s=[];for(var i=0;i<n.length;i++){var l=n[i];if(0===l.refs){for(var a=0;a<l.parts.length;a++)l.parts[a]();delete r[l.id]}}}}function f(e){for(var t=0;t<e.length;t++){var n=e[t],o=r[n.id];if(o){o.refs++;for(var i=0;i<o.parts.length;i++)o.parts[i](n.parts[i]);for(;i<n.parts.length;i++)o.parts.push(b(n.parts[i]));o.parts.length>n.parts.length&&(o.parts.length=n.parts.length)}else{for(var s=[],i=0;i<n.parts.length;i++)s.push(b(n.parts[i]));r[n.id]={id:n.id,refs:1,parts:s}}}}function w(){var e=document.createElement("style");return e.type="text/css",s.appendChild(e),e}function b(e){var t,n,o=document.querySelector("style["+u+'~="'+e.id+'"]');if(o){if(c)return p;o.parentNode.removeChild(o)}if(m){var i=a++;t=_.bind(null,o=l||(l=w()),i,!1),n=_.bind(null,o,i,!0)}else t=v.bind(null,o=w()),n=function(){o.parentNode.removeChild(o)};return t(e),function(o){o?(o.css!==e.css||o.media!==e.media||o.sourceMap!==e.sourceMap)&&t(e=o):n()}}var h=function(){var e=[];return function(t,n){return e[t]=n,e.filter(Boolean).join("\n")}}();function _(e,t,n,o){var i=n?"":o.css;if(e.styleSheet)e.styleSheet.cssText=h(t,i);else{var r=document.createTextNode(i),s=e.childNodes;s[t]&&e.removeChild(s[t]),s.length?e.insertBefore(r,s[t]):e.appendChild(r)}}function v(e,t){var n=t.css,o=t.media,i=t.sourceMap;if(o&&e.setAttribute("media",o),d.ssrId&&e.setAttribute(u,t.id),i&&(n+="\n/*# sourceURL="+i.sources[0]+" */\n/*# sourceMappingURL=data:application/json;base64,"+btoa(unescape(encodeURIComponent(JSON.stringify(i))))+" */"),e.styleSheet)e.styleSheet.cssText=n;else{for(;e.firstChild;)e.removeChild(e.firstChild);e.appendChild(document.createTextNode(n))}}}},t={};function n(o){var i=t[o];if(void 0!==i)return i.exports;var r=t[o]={id:o,exports:{}};return e[o](r,r.exports,n),r.exports}n.d=function(e,t){for(var o in t)n.o(t,o)&&!n.o(e,o)&&Object.defineProperty(e,o,{enumerable:!0,get:t[o]})},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="bundles/aggrowarexoplugin/",window?.__sw__?.assetPath&&(n.p=window.__sw__.assetPath+"/bundles/aggrowarexoplugin/"),function(){"use strict";n(860),Shopware.Component.override("sw-property-detail-base",{template:'{% block sw_property_detail_base_name %}\n\n    <sw-alert variant="info" :title="$tc(\'sw-property.detail.label.customWarexoPropertyTypeAttribute\')" v-if="propertyGroup.customFields?.custom_warexo_property_type == \'attribute\'"></sw-alert>\n    <sw-alert variant="info" :title="$tc(\'sw-property.detail.label.customWarexoPropertyTypeVariantName\')" v-if="propertyGroup.customFields?.custom_warexo_property_type == \'variant_name\'"></sw-alert>\n\n    {% parent %}\n\n{% endblock %}'}),n(464);let{Component:e,State:t}=Shopware;e.register("sw-cms-block-product-options",{template:'\n{% block sw_cms_block_product_options %}\n    <div\n            class="sw-cms-block-product-options"\n            :class="currentDeviceViewClass"\n    >\n        <slot name="content">\n            \n            {% block sw_cms_block_product_options_slot_content %}{% endblock %}\n        </slot>\n    </div>\n{% endblock %}',computed:{currentDeviceView(){return t.get("cmsPageState").currentCmsDeviceView},currentDeviceViewClass(){return this.currentDeviceView?`is--${this.currentDeviceView}`:null}}}),n(536);let{Component:o}=Shopware;o.register("sw-cms-preview-product-options",{template:'{% block sw_cms_block_product_options_preview %}\n    <div class="sw-cms-preview-product-options">\n        <sw-select-field label="Option 1" />\n        <sw-select-field label="Option 2" />\n    </div>\n{% endblock %}'}),Shopware.Service("cmsService").registerCmsBlock({name:"product-options",label:"sw-cms.blocks.commerce.product-options.label",category:"commerce",component:"sw-cms-block-product-options",previewComponent:"sw-cms-preview-product-options",defaultConfig:{marginBottom:"20px",marginTop:"20px",marginLeft:"20px",marginRight:"20px",sizingMode:"boxed"},slots:{content:"product-options"}}),n(697);let{Component:i}=Shopware;i.register("sw-cms-block-gpsr-info",{template:'\n{% block sw_cms_block_gpsr_info %}\n    <div\n            class="sw-cms-block-gpsr-info"\n    >\n        <slot name="content">\n            \n            {% block sw_cms_block_gpsr_info_slot_content %}{% endblock %}\n        </slot>\n    </div>\n{% endblock %}'}),n(392);let{Component:r}=Shopware;r.register("sw-cms-preview-gpsr-info",{template:'{% block sw_cms_block_gpsr_info_preview %}\n    <div class="sw-cms-preview-gpsr-info">\n        Acme Company AG\n        <br>\n        Musterstra\xdfe 12, 12345 Musterstadt, Deutschland\n        <br>\n        info@example.org\n    </div>\n{% endblock %}'}),Shopware.Service("cmsService").registerCmsBlock({name:"gpsr-info",label:"sw-cms.blocks.commerce.gpsr-info.label",category:"commerce",component:"sw-cms-block-gpsr-info",previewComponent:"sw-cms-preview-gpsr-info",defaultConfig:{marginBottom:"20px",marginTop:"20px",marginLeft:"20px",marginRight:"20px",sizingMode:"boxed"},slots:{content:"gpsr-info"}}),n(726);let{Component:s,Mixin:l}=Shopware;s.register("sw-cms-el-product-options",{template:'{% block sw_cms_el_product_options %}\n<div\n    class="sw-cms-el-product-options"\n    :style="alignStyle"\n>\n    <sw-select-field label="Option 1" />\n    <sw-select-field label="Option 2" />\n</div>\n{% endblock %}\n',mixins:[l.getByName("cms-element"),l.getByName("placeholder")],computed:{product(){return this.currentDemoEntity?this.currentDemoEntity:this.element.data?.product?this.element?.data?.product??null:{name:"Lorem Ipsum dolor",productNumber:"XXXXXX",minPurchase:1,deliveryTime:{name:"1-3 days"},price:[{gross:0}]}},pageType(){return this.cmsPageState?.currentPage?.type??""},isProductPageType(){return"product_detail"===this.pageType},alignStyle(){return this.element.config?.alignment?.value?`justify-content: ${this.element.config.alignment.value};`:null},currentDemoEntity(){return"product"===this.cmsPageState.currentMappingEntity?this.cmsPageState.currentDemoEntity:null}},watch:{pageType(e){this.$set(this.element,"locked","product_detail"===e)}},created(){this.createdComponent()},methods:{createdComponent(){this.initElementConfig("product-options"),this.initElementData("product-options"),this.$set(this.element,"locked",this.isProductPageType)}}}),n(763);let{Component:a,Mixin:c}=Shopware,{Criteria:p}=Shopware.Data;a.register("sw-cms-el-config-product-options",{template:'{% block sw_cms_element_product_options_config %}\n<div class="sw-cms-el-config-product-options">\n\n    {% block sw_cms_element_product_options_config_tabs %}\n    <sw-tabs\n        position-identifier="sw-cms-element-config-product-options"\n        class="sw-cms-el-config-product-options__tabs"\n        default-item="content"\n    >\n        <template #default="{ active }">\n\n            {% block sw_cms_element_product_options_config_tab_content %}\n            <sw-tabs-item\n                name="content"\n                :title="$tc(\'sw-cms.elements.general.config.tab.content\')"\n                :active-tab="active"\n            >\n                {{ $tc(\'sw-cms.elements.general.config.tab.content\') }}\n            </sw-tabs-item>\n            {% endblock %}\n\n            {% block sw_cms_element_product_options_config_tab_option %}\n            <sw-tabs-item\n                name="options"\n                :title="$tc(\'sw-cms.elements.general.config.tab.options\')"\n                :active-tab="active"\n            >\n                {{ $tc(\'sw-cms.elements.general.config.tab.options\') }}\n            </sw-tabs-item>\n            {% endblock %}\n        </template>\n\n        <template #content="{ active }">\n            <div\n                v-if="active === \'content\'"\n                class="sw-cms-el-config-product-options__tab-content"\n            >\n                {% block sw_cms_element_product_options_config_content_warning %}\n                <sw-alert\n                    v-if="isProductPage"\n                    class="sw-cms-el-config-product-options__warning"\n                    variant="info"\n                >\n                    {{ $tc(\'sw-cms.elements.configurator.infoText.tooltipSettingDisabled\') }}\n                </sw-alert>\n                {% endblock %}\n\n                {% block sw_cms_element_product_options_config_product_select %}\n                <sw-entity-single-select\n                    v-if="!isProductPage"\n                    ref="cmsProductSelection"\n                    v-model="element.config.product.value"\n                    entity="product"\n                    :label="$tc(\'sw-cms.elements.configurator.config.label.selection\')"\n                    :placeholder="$tc(\'sw-cms.elements.configurator.config.placeholder.selection\')"\n                    :criteria="productCriteria"\n                    :context="productSelectContext"\n                    show-clearable-button\n                    @change="onProductChange"\n                >\n\n                    {% block sw_cms_element_product_options_config_product_variant_label %}\n                    <template #selection-label-property="{ item }">\n                        <sw-product-variant-info :variations="item.variation">\n                            {{ item.translated.name || item.name }}\n                        </sw-product-variant-info>\n                    </template>\n                    {% endblock %}\n\n                    {% block sw_cms_element_product_options_config_product_select_result_item %}\n                    <template #result-item="{ item, index }">\n                        <li\n                            is="sw-select-result"\n                            v-bind="{ item, index }"\n                        >\n\n                            {% block sw_entity_single_select_base_results_list_result_label %}\n                            <span class="sw-select-result__result-item-text">\n                                <sw-product-variant-info :variations="item.variation">\n                                    {{ item.translated.name || item.name }}\n                                </sw-product-variant-info>\n                            </span>\n                            {% endblock %}\n\n                        </li>\n                    </template>\n                    {% endblock %}\n\n                </sw-entity-single-select>\n                {% endblock %}\n            </div>\n\n            <div\n                v-if="active === \'options\'"\n                class="sw-cms-el-config-product-options__tab-options"\n            >\n\n                {% block sw_cms_element_product_options_config_options %}\n                <sw-select-field\n                    v-model="element.config.alignment.value"\n                    class="sw-cms-el-config-product-options__alignment"\n                    :label="$tc(\'sw-cms.elements.general.config.label.alignment\')"\n                    :placeholder="$tc(\'sw-cms.elements.general.config.label.alignment\')"\n                >\n\n                    {% block sw_cms_element_product_box_config_alignment_options %}\n                    <option value="flex-start">\n                        {{ $tc(\'sw-cms.elements.general.config.label.verticalAlignTop\') }}\n                    </option>\n                    <option value="center">\n                        {{ $tc(\'sw-cms.elements.general.config.label.verticalAlignCenter\') }}\n                    </option>\n                    <option value="flex-end">\n                        {{ $tc(\'sw-cms.elements.general.config.label.verticalAlignBottom\') }}\n                    </option>\n                    {% endblock %}\n\n                </sw-select-field>\n                {% endblock %}\n\n            </div>\n        </template>\n    </sw-tabs>\n    {% endblock %}\n\n</div>\n{% endblock %}\n',inject:["repositoryFactory"],mixins:[c.getByName("cms-element")],computed:{productRepository(){return this.repositoryFactory.create("product")},productSelectContext(){return{...Shopware.Context.api,inheritance:!0}},productCriteria(){let e=new p(1,25);return e.addAssociation("options.group"),e},selectedProductCriteria(){let e=new p(1,25);return e.addAssociation("deliveryTime"),e},isProductPage(){return this.cmsPageState?.currentPage?.type==="product_detail"}},created(){this.createdComponent()},methods:{createdComponent(){this.initElementConfig("product-options")},onProductChange(e){e?this.productRepository.get(e,this.productSelectContext,this.selectedProductCriteria).then(t=>{this.element.config.product.value=e,this.$set(this.element.data,"productId",e),this.$set(this.element.data,"product",t)}):(this.element.config.product.value=null,this.$set(this.element.data,"productId",null),this.$set(this.element.data,"product",null)),this.$emit("element-update",this.element)}}}),n(808);let{Component:d}=Shopware;d.register("sw-cms-el-preview-product-options",{template:'{% block sw_cms_element_product_options_preview %}\n<div class="sw-cms-el-preview-product-options">\n    <sw-select-field label="Option 1" />\n</div>\n{% endblock %}\n'});let u=new Shopware.Data.Criteria(1,25);u.addAssociation("deliveryTime"),Shopware.Service("cmsService").registerCmsElement({name:"product-options",label:"sw-cms.elements.product-options.label",component:"sw-cms-el-product-options",configComponent:"sw-cms-el-config-product-options",previewComponent:"sw-cms-el-preview-product-options",disabledConfigInfoTextKey:"sw-cms.elements.product-options.infoText.tooltipSettingDisabled",defaultConfig:{product:{source:"static",value:null,required:!0,entity:{name:"product",criteria:u}},alignment:{source:"static",value:null}},defaultData:{product:{name:"Lorem Ipsum dolor",productNumber:"XXXXXX",minPurchase:1,deliveryTime:{name:"1-3 days"},price:[{gross:0}]}},collect:Shopware.Service("cmsService").getCollectFunction()}),n(73);let{Component:m,Mixin:g}=Shopware;m.register("sw-cms-el-gpsr-info",{template:'{% block sw_cms_el_gpsr_info %}\n<div\n    class="sw-cms-el-gpsr-info"\n>\n    Acme Company AG\n    <br>\n    Musterstra\xdfe 12, 12345 Musterstadt, Deutschland\n    <br>\n    info@example.org\n</div>\n{% endblock %}\n',created(){this.createdComponent()},mixins:[g.getByName("cms-element")],methods:{createdComponent(){this.$set(this.element,"locked",!0),this.initElementConfig("gpsr-info")}}}),n(574);let{Component:f}=Shopware;f.register("sw-cms-el-preview-gpsr-info",{template:'{% block sw_cms_element_gpsr_info_preview %}\n<div class="sw-cms-el-preview-gpsr-info">\n    Acme Company AG\n    <br>\n    Musterstra\xdfe 12, 12345 Musterstadt, Deutschland\n    <br>\n    info@example.org\n</div>\n{% endblock %}\n'}),Shopware.Service("cmsService").registerCmsElement({name:"gpsr-info",label:"sw-cms.elements.gpsr-info.label",component:"sw-cms-el-gpsr-info",previewComponent:"sw-cms-el-preview-gpsr-info",disabledConfigInfoTextKey:"sw-cms.elements.gpsr-info.infoText.tooltipSettingDisabled",defaultConfig:{alignment:{source:"static",value:null}}});var w=[{title:"warexo.product-option.detail.basics.title",ident:"basics",cards:[{title:"warexo.product-option.detail.basics.cards.general.title",ident:"basic-info",columns:"1fr 1fr",fields:[{ref:"name",config:{label:"warexo.product-option.detail.basics.cards.general.name.label",placeholder:"warexo.product-option.detail.basics.cards.general.name.placeholder",required:!0}},{ref:"ident",config:{label:"warexo.product-option.detail.basics.cards.general.ident.label",placeholder:"warexo.product-option.detail.basics.cards.general.ident.placeholder"}},{ref:"position",type:"int",config:{label:"warexo.product-option.detail.basics.cards.general.position.label"}},{ref:"displayType",type:"single-select",required:!0,config:{label:"warexo.product-option.detail.basics.cards.general.displayType.label",options:[{value:"text",label:"warexo.product-option.detail.basics.cards.general.displayType.fixed"},{value:"select",label:"warexo.product-option.detail.basics.cards.general.displayType.select"},{value:"color",label:"warexo.product-option.detail.basics.cards.general.displayType.color"}]}}]},{title:"warexo.product-option.detail.basics.cards.description.title",ident:"description",columns:"1fr",fields:[{ref:"description",type:"textarea",config:{}}]}]},{title:"warexo.product-option.detail.options.title",ident:"options",cards:[{title:"warexo.product-option.detail.options.cards.options.title",ident:"options",grid:{ref:"productOptionValues",columns:[{property:"name",label:"warexo.product-option.detail.options.cards.options.columnName",inlineEdit:"string",primary:!0},{property:"colorHexCode",label:"warexo.product-option.detail.options.cards.options.columnColor"},{property:"position",label:"warexo.product-option.detail.options.cards.options.columnPosition",inlineEdit:"number"}],fields:[{ref:"name",config:{label:"warexo.product-option.detail.options.cards.options.name.label",required:!0,validation:"required"}},{ref:"position",type:"int",config:{label:"warexo.product-option.detail.options.cards.options.position.label"}},{ref:"colorHexCode",type:"colorpicker",config:{zIndex:1e3,label:"warexo.product-option.detail.options.cards.options.color.label"}},{ref:"mediaId",config:{componentName:"sw-media-field",label:"warexo.product-option.detail.options.cards.options.media.label"}},{ref:"description",type:"textarea",config:{label:"warexo.product-option.detail.options.cards.options.description.label"}}]}}]}];let b="warexo_product_option",h="warexo.product.option.detail",_="warexo.product.option.list";var v=JSON.parse('{"warexo":{"product-option":{"mainMenuItemGeneral":"Auswahllisten","descriptionTextModule":"Auswahllisten","list":{"header":"Auswahllisten","add":"Auswahlliste hinzuf\xfcgen","columnName":"Name","columnIdent":"Ident","columnPosition":"Position"},"detail":{"basics":{"title":"Allgemein","cards":{"general":{"title":"Allgemeine Informationen","ident":{"label":"Ident","placeholder":"Technischer Name"},"name":{"label":"Name","placeholder":"Bezeichung der Auswahlliste"},"position":{"label":"Position","helpText":"Auswahllisten werden nach Position sortiert."},"displayType":{"label":"Anzeige Art","fixed":"Text","select":"Dropdown","color":"Farbe/Bild"}},"description":{"title":"Beschreibung"}}},"options":{"title":"Optionen","cards":{"options":{"title":"Optionen","columnName":"Name","columnPosition":"Position","columnColor":"Farbwert","name":{"label":"Name"},"position":{"label":"Position"},"color":{"label":"Farbwert"},"media":{"label":"Bild"},"description":{"label":"Beschreibung"}}}}}}}}'),y=JSON.parse('{"warexo":{"product-option":{"mainMenuItemGeneral":"Auswahllisten","descriptionTextModule":"Auswahllisten","list":{"header":"Auswahllisten","add":"Auswahlliste hinzuf\xfcgen","columnName":"Name","columnIdent":"Ident","columnPosition":"Position"},"detail":{"basics":{"title":"Allgemein","cards":{"general":{"title":"Allgemeine Informationen","ident":{"label":"Ident","placeholder":"Technischer Name"},"name":{"label":"Name","placeholder":"Bezeichung der Auswahlliste"},"position":{"label":"Position","helpText":"Auswahllisten werden nach Position sortiert."},"displayType":{"label":"Anzeige Art","fixed":"Text","select":"Dropdown","color":"Farbe/Bild"}},"description":{"title":"Beschreibung"}}},"options":{"title":"Optionen","cards":{"options":{"title":"Optionen","columnName":"Name","columnPosition":"Position","columnColor":"Farbwert","name":{"label":"Name"},"position":{"label":"Position"},"color":{"label":"Farbwert"},"media":{"label":"Bild"},"description":{"label":"Beschreibung"}}}}}}}}');Shopware.Module.register("warexo-product-option",{type:"plugin",name:"Product Options",title:"warexo.product-option.mainMenuItemGeneral",description:"warexo.product-option.descriptionTextModule",color:"#00af64",icon:"regular-shopping-bag",snippets:{"de-DE":v,"en-GB":y},routes:{list:{component:"aggro-entity-list",path:"list",props:{default:{entity:b,columns:[{property:"name",dataIndex:"name",allowResize:!0,routerLink:"warexo.product.option.detail",label:"warexo.product-option.list.columnName",inlineEdit:"string",primary:!0},{property:"ident",allowResize:!0,label:"warexo.product-option.list.columnIdent",inlineEdit:"string"},{property:"position",allowResize:!0,label:"warexo.product-option.list.columnPosition",inlineEdit:"int"}],labels:{header:"warexo.product-option.list.header",add:"warexo.product-option.list.add"},links:{create:"warexo.product.option.create",detail:h}}}},create:{component:"aggro-entity-detail",path:"create",props:{default:{entity:b,forms:w,links:{list:_,detail:h}}},meta:{parentPath:_}},detail:{component:"aggro-entity-detail",path:"detail/:id",props:{default(e){return{entityId:e.params.id,entity:b,forms:w,links:{list:_,detail:h}}}},meta:{parentPath:_}}},navigation:[{label:"warexo.product-option.mainMenuItemGeneral",color:"#00af64",path:"warexo.product.option.list",icon:"regular-shopping-bag",parent:"sw-catalogue",position:100}]});var x=JSON.parse('{"sw-cms":{"blocks":{"commerce":{"product-options":{"label":"Warexo Produkt Optionen"},"gpsr-info":{"label":"GPSR Informationen"}}},"elements":{"product-options":{"label":"Warexo Produkt Optionen"},"gpsr-info":{"label":"GPSR Informationen"}}},"sw-property":{"detail":{"label":{"customWarexoPropertyTypeAttribute":"Es handelt sich hierbei um ein aus Warexo importiertes Attribut","customWarexoPropertyTypeVariantName":"Es handelt sich hierbei um einen aus Warexo importierten Namen der Auswahl einer Variante"}}}}'),k=JSON.parse('{"sw-cms":{"blocks":{"commerce":{"product-options":{"label":"Warexo Produkt Optionen"},"gpsr-info":{"label":"GPSR Informationen"}}},"elements":{"product-options":{"label":"Warexo Produkt Optionen"},"gpsr-info":{"label":"GPSR Informationen"}}},"sw-property":{"detail":{"label":{"customWarexoPropertyTypeAttribute":"Es handelt sich hierbei um ein aus Warexo importiertes Attribut","customWarexoPropertyTypeVariantName":"Es handelt sich hierbei um einen aus Warexo importierten Namen der Auswahl einer Variante"}}}}');Shopware.Locale.extend("de-DE",x),Shopware.Locale.extend("en-GB",k)}()})();