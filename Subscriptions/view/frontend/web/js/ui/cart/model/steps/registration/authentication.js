/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define(
    [
        'jquery',
        'Magento_Ui/js/form/form',
        './login',
        'Magento_Customer/js/model/customer',
        'mage/validation',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/url'
    ],
    function($, Component, loginAction, customer, validation, fullScreenLoader, url) {
        'use strict';

        return Component.extend({
            isGuestCheckoutAllowed: true,
            isCustomerLoginRequired: true,
            autocomplete: false,
            defaults: {
                forgotPasswordUrl: '',
                registerUrl: '',
                baseUrl: '',
                template: '',
                placeholderPassword: $.mage.__('Password'),
                placeholderEmail: $.mage.__('Email Address')
            },

            /**
             * Init
             */
            initialize: function () {
                var self = this;
                this._super();
                url.setBaseUrl(this.baseUrl);
            },


            /** Is login form enabled for current customer */
            isActive: function() {
                return !customer.isLoggedIn();
            },

            /** Provide login action */
            login: function(loginForm) {
                var loginData = {},
                    formDataArray = $(loginForm).serializeArray();

                formDataArray.forEach(function (entry) {
                    loginData[entry.name] = entry.value;
                });

                if($(loginForm).validation()
                    && $(loginForm).validation('isValid')
                ) {
                    fullScreenLoader.startLoader();
                    loginAction(loginData, '', undefined).always(function() {
                        fullScreenLoader.stopLoader();
                    });
                }
            }
        });
    }
);
