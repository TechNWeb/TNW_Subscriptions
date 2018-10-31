/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'ko',
    'jquery',
    'underscore',
    'mage/translate',
    'uiComponent',
    'Magento_Customer/js/customer-data',
    'Magento_Customer/js/model/authentication-popup'
], function (ko, $, _, $t, Component, customerData, authenticationPopup) {
    'use strict';

    var sidebarInitialized = false,
        addToCartCalls = 0;

    return Component.extend({
        shoppingCartUrl: window.subcheckout.shoppingCartUrl,
        cart: {},

        /**
         * @inheritdoc
         */
        initialize: function () {
            var self = this,
                cartData = customerData.get('tnw-subscriptions-subscription-cart');

            this.update(cartData());
            cartData.subscribe(function (updatedCart) {
                addToCartCalls--;
                this.isLoading(addToCartCalls > 0);
                sidebarInitialized = false;
                this.update(updatedCart);
            }, this);

            $('[data-block="minicart"]').on('contentLoading', function () {
                addToCartCalls++;
                self.isLoading(true);
            });

            if (cartData()['website_id'] !== window.subcheckout.websiteId) {
                customerData.reload(['tnw-subscriptions-subscription-cart'], false);
            }

            return this._super();
        },
        isLoading: ko.observable(false),

        /**
         * Update mini shopping cart content.
         *
         * @param {Object} updatedCart
         * @returns void
         */
        update: function (updatedCart) {
            _.each(updatedCart, function (value, key) {
                if (!this.cart.hasOwnProperty(key)) {
                    this.cart[key] = ko.observable();
                }
                this.cart[key](value);
            }, this);
        },

        /**
         * Get cart param by name.
         * @param {String} name
         * @returns {*}
         */
        getCartParam: function (name) {
            if (!_.isUndefined(name)) {
                if (!this.cart.hasOwnProperty(name)) {
                    this.cart[name] = ko.observable();
                }
            }

            return this.cart[name]();
        },

        /**
         * Close mini shopping cart.
         */
        closeMinicart: function () {
            $('[data-block="tnw-subscriptions-minicart"]').find('[data-role="dropdownDialog"]').dropdownDialog('close');
        },

        /**
         * @return {boolean}
         */
        actionCheckout: function () {
            var cart = customerData.get('tnw-subscriptions-subscription-cart'),
                customer = customerData.get('customer');

            this.closeMinicart();

            if (!customer().firstname && cart().isGuestCheckoutAllowed === false) {
                // set URL for redirect on successful login/registration. It's postprocessed on backend.
                $.cookie('login_redirect', window.subcheckout.checkoutUrl);

                if (window.subcheckout.isRedirectRequired) {
                    location.href = window.subcheckout.customerLoginUrl;
                } else {
                    authenticationPopup.showModal();
                }

                return false;
            }
            location.href = window.subcheckout.checkoutUrl;
            return true;
        },

        /**
         * Returns count of cart line items
         * @returns {Number}
         */
        getCartLineItemsCount: function () {
            var items = this.getCartParam('items') || [];

            return parseInt(items.length, 10);
        }
    });
});
