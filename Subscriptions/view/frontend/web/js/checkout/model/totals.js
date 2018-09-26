/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'ko',
    'TNW_Subscriptions/js/checkout/model/quote',
    'Magento_Customer/js/customer-data'
], function (ko, quote, customerData) {
    'use strict';

    var quoteItems = ko.observable(quote.totals().items),
        cartData = customerData.get('tnw-subscriptions-subscription-cart'),
        quoteSubtotal = parseFloat(quote.totals().subtotal),
        subtotalAmount = parseFloat(cartData().subtotalAmount);

    quote.totals.subscribe(function (newValue) {
        quoteItems(newValue.items);
    });

    if (quoteSubtotal !== subtotalAmount) {
        customerData.reload(['tnw-subscriptions-subscription-cart'], false);
    }

    return {
        totals: quote.totals,
        isLoading: ko.observable(false),

        /**
         * @return {Function}
         */
        getItems: function () {
            return quoteItems;
        },

        /**
         * @param {*} itemId
         * @return {*}
         */
        getItem: function (itemId) {
            var i, item;

            if (!quoteItems()) {
                return null;
            }

            for (i in quoteItems()) {
                item = quoteItems()[i];

                if (Number.parseInt(item['item_id']) === Number.parseInt(itemId)) {
                    return item;
                }
            }

            return null;
        },

        /**
         * @param {*} code
         * @return {*}
         */
        getSegment: function (code) {
            var i, total;

            if (!this.totals()) {
                return null;
            }

            for (i in this.totals()['total_segments']) {
                total = this.totals()['total_segments'][i];

                if (total.code === code) {
                    return total;
                }
            }

            return null;
        }
    };
});
