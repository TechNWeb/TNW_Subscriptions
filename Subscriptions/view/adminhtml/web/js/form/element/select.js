/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
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
        defaults: {
            listens: {
                value: 'onChange'
            }
        },

        /** @inheritdoc */
        initObservable: function () {
            return this._super()
                .observe([
                    'disabled',
                ]);
        },


        /**
         * Setting new selected currency and reload subscription products prices
         *
         * @param value
         */
        onChange: function (value) {
            var subProductListing = registry.get('index=tnw_subscriptionprofile_create_product_listing');
            subProductListing.source.set('params.currency_id', value);
            subProductListing.source.set('params.t', Date.now());
        },
    });
});
