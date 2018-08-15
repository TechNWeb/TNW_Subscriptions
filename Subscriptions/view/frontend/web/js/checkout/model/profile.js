/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'ko',
    'underscore'
], function (ko, _) {
    'use strict';

    /**
     * Get totals data from the extension attributes.
     * @param {*} data
     * @returns {*}
     */
    var proceedTotalsData = function (data) {
            if (_.isObject(data) && _.isObject(data['extension_attributes'])) {
                _.each(data['extension_attributes'], function (element, index) {
                    data[index] = element;
                });
            }

            return data;
        },
        billingAddress = ko.observable(null),
        shippingAddress = ko.observable(null),
        shippingMethod = ko.observable(null),
        paymentMethod = ko.observable(null),
        basePriceFormat = window.checkoutConfig.basePriceFormat,
        priceFormat = window.checkoutConfig.priceFormat,
        storeCode = window.checkoutConfig.storeCode,
        totalsData = proceedTotalsData([]),
        totals = ko.observable(totalsData),
        collectedTotals = ko.observable({});

    return {
        totals: totals,
        shippingAddress: shippingAddress,
        shippingMethod: shippingMethod,
        billingAddress: billingAddress,
        paymentMethod: paymentMethod,
        guestEmail: null,

        /**
         * @return {Boolean}
         */
        isVirtual: function () {
            return false;
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
         *
         * @return {*}
         */
        getTotals: function () {
            return totals;
        },

        /**
         * @param {Object} data
         */
        setTotals: function (data) {
            data = proceedTotalsData(data);
            totals(data);
            this.setCollectedTotals('subtotal_with_discount', parseFloat(data['subtotal_with_discount']));
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
         * @return {*}
         */
        getStoreCode: function () {
            return storeCode;
        },

        /**
         * @param {String} code
         * @param {*} value
         */
        setCollectedTotals: function (code, value) {
            var colTotals = collectedTotals();

            colTotals[code] = value;
            collectedTotals(colTotals);
        },

        /**
         * @return {Number}
         */
        getCalculatedTotal: function () {
            var total = 0.; //eslint-disable-line no-floating-decimal

            _.each(collectedTotals(), function (value) {
                total += value;
            });

            return total;
        }
    };
});
