/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/url-builder',
    'mageUtils'
], function (customer, urlBuilder, utils) {
    'use strict';

    return {
        /**
         * @param {Object} quote
         * @return {*}
         */
        getUrlForTotalsEstimationForNewAddress: function (quote) {
            return this.getUrl({
                'guest': '/guest-carts/:cartId/totals-information',
                'customer': '/tnw-subscriptions-carts/mine/totals-information'
            }, {cartId: quote.getQuoteId()});
        },

        /**
         * @param {Object} quote
         * @return {*}
         */
        getUrlForEstimationShippingMethodsForNewAddress: function (quote) {
            return this.getUrl({
                'guest': '/guest-carts/:quoteId/estimate-shipping-methods',
                'customer': '/tnw-subscriptions-carts/mine/estimate-shipping-methods'
            }, {quoteId: quote.getQuoteId()});
        },

        /**
         * @param {Object} quote
         * @return {*}
         */
        getUrlForEstimationShippingMethodsByAddressId: function () {
            return this.getUrl({
                'default': '/tnw-subscriptions-carts/mine/estimate-shipping-methods-by-address-id'
            }, {});
        },

        /**
         * @param {String} couponCode
         * @param {String} quoteId
         * @return {*}
         */
        getApplyCouponUrl: function (couponCode, quoteId) {
            return this.getUrl({
                'guest': '/guest-carts/:quoteId/coupons/:couponCode',
                'customer': '/tnw-subscriptions-carts/mine/coupons/:couponCode'
            }, {quoteId: quoteId, couponCode: encodeURIComponent(couponCode)});
        },

        /**
         * @param {String} quoteId
         * @return {*}
         */
        getCancelCouponUrl: function (quoteId) {
            return this.getUrl({
                'guest': '/guest-carts/:quoteId/coupons/',
                'customer': '/tnw-subscriptions-carts/mine/coupons/'
            }, {quoteId: quoteId});
        },

        /**
         * @param {Object} quote
         * @return {*}
         */
        getUrlForCartTotals: function (quote) {
            return this.getUrl({
                'guest': '/guest-carts/:quoteId/totals',
                'customer': '/tnw-subscriptions-carts/mine/totals'
            }, {quoteId: quote.getQuoteId()});
        },

        /**
         * @param {Object} quote
         * @return {*}
         */
        getUrlForSetShippingInformation: function (quote) {
            return this.getUrl({
                'guest': '/guest-carts/:cartId/shipping-information',
                'customer': '/tnw-subscriptions-carts/mine/shipping-information'
            }, {cartId: quote.getQuoteId()});
        },

        /**
         * Get url for service.
         *
         * @param {*} urls
         * @param {*} urlParams
         * @return {String|*}
         */
        getUrl: function (urls, urlParams) {
            var url, checkoutMethod = customer.isLoggedIn() ? 'customer' : 'guest';

            if (utils.isEmpty(urls)) {
                return 'Provided service call does not exist.';
            }

            if (!utils.isEmpty(urls['default'])) {
                url = urls['default'];
            } else {
                url = urls[checkoutMethod];
            }

            return urlBuilder.createUrl(url, urlParams);
        }
    };
});
