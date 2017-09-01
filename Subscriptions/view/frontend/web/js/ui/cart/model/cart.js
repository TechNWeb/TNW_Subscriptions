/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(
    [
        'jquery',
        'ko'
    ],
    function($, ko) {
        'use strict';

        var cartData = ko.observable(window.tnwSubscriptionsCheckoutConfig.subscriptionsCart);

        return {
            cartData: cartData,

            /**
             * Get cart Id
             *
             * @returns {number}
             */
            getCartId: function () {
                return 3;
            },

            /**
             * Get price format
             *
             * @returns {Object}
             */
            getPriceFormat: function () {
                return window.tnwSubscriptionsCheckoutConfig.priceFormat;
            },

            /**
             * Get items count
             *
             * @returns {number}
             */
            getItemsCount: function () {
                var itemsCount = 0;

                $.each(this.cartItems(), function () {
                    if (!this.is_deleted) {
                        itemsCount++;
                    }
                });

                return itemsCount;
            },

            /**
             * Get subscription plan Id
             *
             * @returns {number}
             */
            getSubscriptionPlanId: function () {
                return this.planId;
            },

            /**
             * Get is subscription plan selected flag
             *
             * @returns {boolean}
             */
            isSubscriptionPlanSelected: function () {
                return this.isPlanSelected;
            },

            /**
             * Set cart data
             *
             * @param {Array} cartData
             */
            setCartData: function (cartData) {
                this.cartData(cartData);
            },

            /**
             * Get address by type
             *
             * @param {String} addressType
             * @returns {Object}
             */
            getAddressByType: function (addressType) {
               /* var cartAddresses = this.cartData().addresses;

                return cartAddresses
                    ? _.find(cartAddresses, function (address) {
                        return address.address_type == addressType
                    })
                    : {};*/
            }
        };
    }
);
