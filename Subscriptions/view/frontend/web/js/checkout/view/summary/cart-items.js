/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'ko',
    'uiComponent',
    'TNW_Subscriptions/js/checkout/model/totals',
    'TNW_Subscriptions/js/checkout/model/quote'
], function (ko, Component, totals, quote) {
    'use strict';

    var imageData = window.checkoutConfig.imageData;

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
            return totals.getItem(itemId);
        },

        getItems: function (group) {
            return group['itemIds'].map(function (itemId) {
                return this.getTotalItem(itemId);
            }.bind(this));
        },

        /**
         * @param {Object} item
         * @return {Array}
         */
        getImageItem: function (item) {
            if (imageData[item]) {
                return imageData[item['item_id']];
            }

            return [];
        },

        getProfileName: function (group) {
            return group.caption;
        },

        getProfileDescription: function (group) {
            return group.description;
        }
    });
});
