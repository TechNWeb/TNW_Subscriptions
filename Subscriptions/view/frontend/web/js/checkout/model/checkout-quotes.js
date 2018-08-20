/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'ko',
    'underscore',
    'TNW_Subscriptions/js/checkout/model/quote'
], function (ko, _, Quote) {
    'use strict';

    /**
     * Get totals data from the extension attributes.
     * @param {*} data
     * @returns {*}
     */
    var billingAddress = ko.observable(null),
        shippingAddress = ko.observable(null),
        shippingMethod = ko.observable(null),
        paymentMethod = ko.observable(null),
        basePriceFormat = window.checkoutConfig.basePriceFormat,
        priceFormat = window.checkoutConfig.priceFormat,
        storeCode = window.checkoutConfig.storeCode;

    return {
        shippingAddress: shippingAddress,
        shippingMethod: shippingMethod,
        billingAddress: billingAddress,
        paymentMethod: paymentMethod,
        guestEmail: null,

        /**
         * @return {Boolean}
         */
        isVirtual: function () {
            return _.chain(this.getQuotes())
                .map(function(quote){
                    return quote.isVirtual();
                })
                .find(function (virtual) {
                    return virtual === false
                })
                .isUndefined()
                .value();
        },

        /**
         * @return {*}
         */
        getPriceFormat: function () {
            return priceFormat;
        },

        /**
         * @return {*}
         */
        getBasePriceFormat: function () {
            return basePriceFormat;
        },

        /**
         * @return {*}
         */
        getStoreCode: function () {
            return storeCode;
        },

        /**
         * @param {*} paymentMethodCode
         */
        setPaymentMethod: function (paymentMethodCode) {
            paymentMethod(paymentMethodCode);
        },

        /**
         * @return {*}
         */
        getPaymentMethod: function () {
            return paymentMethod;
        },

        /**
         * @return {Array}
         */
        getQuotes: function () {
            var quotes = [],
                checkoutConfig = window.checkoutConfig;

            if (Object.keys(checkoutConfig).length) {
                _.each(checkoutConfig.quotes, function (quoteData) {
                    quotes.push(new Quote(quoteData));
                });
            }

            return quotes;
        }
    };
});
