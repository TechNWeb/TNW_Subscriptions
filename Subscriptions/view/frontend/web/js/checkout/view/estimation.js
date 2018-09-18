/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'uiComponent',
    'TNW_Subscriptions/js/checkout/model/quote',
    'Magento_Catalog/js/price-utils',
    'TNW_Subscriptions/js/checkout/model/totals',
    'TNW_Subscriptions/js/checkout/model/sidebar'
], function (Component, quote, priceUtils, totals, sidebarModel) {
    'use strict';

    return Component.extend({
        isLoading: totals.isLoading,

        /**
         * @return {Number}
         */
        getQuantity: function () {
            if (totals.totals()) {
                return parseFloat(totals.totals()['items_qty']);
            }

            return 0;
        },

        /**
         * @return {Number}
         */
        getPureValue: function () {
            if (totals.totals()) {
                return parseFloat(totals.getSegment('grand_total').value);
            }

            return 0;
        },

        /**
         * Show sidebar.
         */
        showSidebar: function () {
            sidebarModel.show();
        },

        /**
         * @param {*} price
         * @return {*|String}
         */
        getFormattedPrice: function (price) {
            return priceUtils.formatPrice(price, quote.getPriceFormat());
        },

        /**
         * @return {*|String}
         */
        getValue: function () {
            return this.getFormattedPrice(this.getPureValue());
        }
    });
});
