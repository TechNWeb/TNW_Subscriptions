/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'underscore',
    'Magento_Ui/js/grid/listing',
    'rjsResolver',
    'uiRegistry'
], function (_, Listing, resolver, registry) {
    'use strict';

    return Listing.extend({
        defaults: {
            template: 'TNW_Subscriptions/grid/subscription-listing',
            estimatedPayment: null
        },

        /**
         * Initializes observable properties.
         */
        initObservable: function () {
            this._super()
                .observe(
                    'estimatedPayment'
                );

            return this;
        },

        showBottomBorder: function (row) {
            var lastRow,
                items;

            items = this.source.data.items;
            lastRow = items[items.length - 1];

            return items.length > 1 && lastRow.title !== row.title;
        },

        /**
         * Handler of the data providers' 'reloaded' event.
         */
        onDataReloaded: function () {
            var addButton,
                modifyButton,
                show;

            this.set('estimatedPayment', this.source.data.estimatedPayment);

            resolver(this.hideLoader, this);

            addButton = registry.get('index=button_add_product');
            modifyButton = registry.get('index=button_modify_subscriptions');

            show = this.rows.length > 0;

            if (addButton){
                addButton.set('displayPrimary', !show);
            }

            if (modifyButton){
                modifyButton.set('visible', show);
            }
        }
    });
});
