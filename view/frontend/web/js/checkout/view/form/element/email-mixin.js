/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'TNW_Subscriptions/js/customer/action/check-email-availability',
    'Magento_Customer/js/action/login',
    'TNW_Subscriptions/js/checkout/view/checkout-force-login-status',
], function (
    $,
    customerData,
    quote,
    checkoutData,
    checkEmailAvailability,
    loginAction,
    checkoutForceLoginStatus
) {
    'use strict';

    return function (originalEmail) {
        return originalEmail.extend({
            defaults: {
                forceLogin: false
            },

            /**
             * @inheritDoc
             */
            initialize: function () {
                this._super();

                if (this.isPasswordVisible()) {
                    this.checkEmailAvailability();
                }

                return this;
            },

            /**
             * @inheritDoc
             */
            initConfig: function () {
                this._super();

                this.forceLogin = this.resolveForceLoginRequirement();
                checkoutForceLoginStatus(this.forceLogin)

                return this;
            },

            /**
             * @inheritDoc
             */
            initObservable: function () {
                this._super()
                    .observe(['forceLogin']);

                return this;
            },

            /**
             * @inheritDoc
             */
            checkEmailAvailability: function () {
                this.validateRequest();
                this.isEmailCheckComplete = $.Deferred();
                this.isLoading(true);
                this.checkRequest = checkEmailAvailability(this.isEmailCheckComplete, this.email(), quote.getQuoteId());

                $.when(this.isEmailCheckComplete).done(function (status) {
                    var IS_CUSTOMER_GUEST = 0,
                        IS_CUSTOMER_EXISTS = 8,
                        IS_SUBSCRIBE_ACTIVE = 16;

                    if (status === IS_CUSTOMER_EXISTS) {
                        this.isPasswordVisible(true);
                        this.forceLogin(false)
                        checkoutData.setCheckedEmailValue(this.email());
                        checkoutData.setForceLoginValue(this.forceLogin());
                        checkoutForceLoginStatus(false)
                    } else if (status === (IS_CUSTOMER_EXISTS | IS_SUBSCRIBE_ACTIVE)) {
                        this.isPasswordVisible(true);
                        this.forceLogin(true)
                        checkoutData.setCheckedEmailValue(this.email());
                        checkoutData.setForceLoginValue(this.forceLogin());
                        checkoutForceLoginStatus(true)
                    } else {
                        this.isPasswordVisible(false);
                        this.forceLogin(false)
                        checkoutData.setCheckedEmailValue('');
                        checkoutData.setForceLoginValue(this.forceLogin());
                        checkoutForceLoginStatus(false)
                    }
                }.bind(this)).fail(function () {
                    this.isPasswordVisible(false);
                    this.forceLogin(false)
                    checkoutData.setCheckedEmailValue('');
                    checkoutData.setForceLoginValue(this.forceLogin());
                    checkoutForceLoginStatus(false)
                }.bind(this)).always(function () {
                    this.isLoading(false);
                }.bind(this));
            },

            login: function (loginForm) {
                loginAction.registerLoginCallback(function () {
                    this.forceLogin(false)
                    checkoutData.setForceLoginValue(this.forceLogin());
                }.bind(this));
                this._super(loginForm);
            },

            /**
             * Resolves an initial state of a force login requirement.
             *
             * @returns {Boolean} - initial state.
             */
            resolveForceLoginRequirement: function () {
                return (this.isPasswordVisible && checkoutData.getForceLoginValue());
            }
        });
    };
});
