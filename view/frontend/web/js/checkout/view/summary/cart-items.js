/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'ko',
    'uiComponent',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/quote'
], function (ko, Component, totals, quote) {
    'use strict';

    return Component.extend({
        groups: ko.observable([]),

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            // Set initial groups to observable field
            this.groups(quote.getGroups());

            // Subscribe for items data changes and refresh items in view
            totals.getItems().subscribe(function (items) {
                this.groups.valueHasMutated();
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
            return group['itemIds'].map(function (itemId) {
                return this.getTotalItem(itemId);
            }.bind(this));
        },

        /**
         * @param {Object} item
         * @return {boolean}
         */
        allowDisplayQtyItem: function (item) {
            var quoteItem = quote.getItem(item['item_id']);
            if (null !== quoteItem) {
                return quoteItem['tnw_subscr_hide_qty'] == "0" ? true : false;
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
});
