/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'underscore',
    'Magento_Checkout/js/model/quote'
], function (_, quote) {
    'use strict';

    return _.extend(quote, {
        /**
         * @return {*}
         */
        getItem: function (itemId) {
            var i, item, quoteItemData = window.checkoutConfig.quoteItemData;

            for (i in quoteItemData) {
                item = quoteItemData[i];

                if (item['item_id'] === itemId) {
                    return item;
                }
            }

            return null;
        },

        /**
         * @return {*}
         */
        getGroups: function () {
            return window.checkoutConfig.quoteGroupData;
        }
    });
});
