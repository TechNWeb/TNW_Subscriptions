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
                if (!quoteItemData.hasOwnProperty(i)) {
                    continue;
                }

                item = quoteItemData[i];
                if (Number.parseInt(item['item_id']) === Number.parseInt(itemId)) {
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
