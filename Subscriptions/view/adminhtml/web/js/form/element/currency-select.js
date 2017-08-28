/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'underscore',
    'mageUtils',
    'uiRegistry',
    'Magento_Ui/js/form/element/select',
    'uiLayout'
], function (_, utils, registry, Abstract, layout) {
    'use strict';

    return Abstract.extend({

        /** @inheritdoc */
        initObservable: function () {
            return this._super()
                .observe([
                    'disabled'
                ]);
        },

        /**
         * Setting new selected currency and reload subscription products prices
         *
         * @return {void}
         */
        onUpdate: function () {
            this._super();
            var subProductListing = registry.get('index=tnw_subscriptionprofile_create_product_listing');
            if (subProductListing) {
                subProductListing.source.set('params.currency_id', this.value());
                subProductListing.source.set('params.t', Date.now());
            }
        }
    });
});
