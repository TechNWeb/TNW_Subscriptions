/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'underscore',
    'Magento_Ui/js/grid/listing',
    'rjsResolver',
    'uiRegistry',
    'jquery',
    'Magento_Ui/js/lib/spinner'
], function (_, Listing, resolver, registry, $j, loader) {
    'use strict';

    return Listing.extend({
        defaults: {
            template: 'TNW_Subscriptions/grid/subscription-listing',
            estimatedPayment: null,
            useParentLoader: false,
            parentForLoader: ''
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
            this._super();

            var addButton,
                modifyButton,
                continueButton,
                saveFormButton,
                reviewAndCreateButton,
                show,
                currencySelect;

            this.set('estimatedPayment', this.source.data.estimatedPayment);

            addButton = registry.get('index=button_add_product');
            modifyButton = registry.get('index=button_modify_subscriptions');
            saveFormButton = $j('#save');
            continueButton = registry.get('index=continue');
            reviewAndCreateButton = registry.get('index=review_and_create');
            currencySelect = registry.get('index=currency_id');

            show = this.rows.length > 0;

            if (addButton){
                addButton.set('displayPrimary', !show);
            }

            if (modifyButton){
                modifyButton.set('visible', show);
            }

            if (continueButton){
                continueButton.set('disabled', !show);
            }

            if (reviewAndCreateButton){
                reviewAndCreateButton.set('disabled', !show);
            }

            if (currencySelect){
                currencySelect.set('disabled', show);
            }

            // main save button near 'cancel' or 'back'
            saveFormButton.prop('disabled', !show);
        },

        /**
         * Hide loader for this listing or for parent element.
         *
         * @returns {Object}
         */
        hideLoader: function () {
            if (this.useParentLoader) {
                registry.get(this.parentForLoader).removeFromLoadingQueue(this.ns);
            } else {
                loader.get(this.name).hide();
            }
        },

        /**
         * Show loader for this listing or for parent element.
         *
         * @returns {Object}
         */
        showLoader: function () {
            if (this.useParentLoader) {
                registry.get(this.parentForLoader).addToLoadingQueue(this.ns);
            } else {
                loader.get(this.name).show();
            }
        }
    });
});
