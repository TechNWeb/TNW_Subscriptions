/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/customer-data'
], function (ko, Component, totals, quote, customerData) {
    'use strict';

    return function(CartItems) {

        return CartItems.extend({
            groups: ko.observable([]),

            /**
             * @inheritdoc
             */
            initialize: function () {
                this._super();
                // Set initial groups to observable field
                this.groups(quote.getGroups());
                if (window.checkoutConfig && window.checkoutConfig.isNegotiableQuote) {
                    return
                }
                customerData.get('cart').subscribe(function (cartSection) {
                    this.groups(cartSection['quoteGroupData']);
                }.bind(this));
            },

            getTotalItem: function (itemId) {
                var i, quoteItems = totals.getItems()();

                if (!quoteItems) {
                    return null;
                }

                for (i in quoteItems) {
                    if (!quoteItems.hasOwnProperty(i)) {
                        continue;
                    }

                    if (Number.parseInt(quoteItems[i]['item_id']) === Number.parseInt(itemId)) {
                        return quoteItems[i];
                    }
                }

                return null;
            },

            getItems: function (group) {
                return _.compact(group['itemIds'].map(function (itemId) {
                    return this.getTotalItem(itemId);
                }.bind(this)));
            },

            /**
             * @param {Object} item
             * @return {boolean}
             */
            allowDisplayQtyItem: function (item) {
                var quoteItem = quote.getItem(item['item_id']);
                if (null !== quoteItem && quoteItem['product']['tnw_subscr_hide_qty'] !== undefined) {
                    return Number.parseInt(quoteItem['product']['tnw_subscr_hide_qty']) === 0;
                }
                return true;
            },

            getProfileName: function (group) {
                return group.caption;
            },

            getProfileDescription: function (group) {
                return group.description;
            }
        });
    };
});
