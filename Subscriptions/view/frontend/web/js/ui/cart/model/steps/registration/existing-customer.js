/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'uiComponent',
    'jquery'
], function (uiComponent, $) {
    'use strict';

    return uiComponent.extend({
        defaults: {
            forgotPasswordUrl: window.tnwSubscriptionsCheckoutConfig.forgotPasswordUrl,
            loginPostUrl: window.tnwSubscriptionsCheckoutConfig.loginPostUrl,
            placeholderPassword: $.mage.__('Password'),
            placeholderEmail: $.mage.__('Email Address'),
            emailFocused: false,
            email: '',
            listens: {
                emailFocused: 'validateEmail',
            }
        },

        /**
         * @inheritDoc
         */
        initObservable: function () {
            this._super()
                .observe(['email', 'emailFocused']);

            return this;
        },

        /**
         * Local email validation.
         *
         * @param {Boolean} focused - input focus.
         * @param {Boolean} fromForm.
         *
         * @returns {Boolean} - validation result.
         */
        validateEmail: function (focused, fromForm) {
            var loginFormSelector = 'form[data-role=email-for-login]',
                usernameSelector = loginFormSelector + ' input[name="login[username]"]',
                loginForm = $(loginFormSelector),
                validator;

            loginForm.validation();

            if (focused === false && !!this.email()) {
                return !!$(usernameSelector).valid();
            }

            if (focused === false && fromForm === true) {
                return !!$(usernameSelector).valid();
            }

            validator = loginForm.validate();

            return validator.check(usernameSelector);
        },

        validatePassword: function () {
            var loginFormSelector = 'form[data-role=email-for-login]',
                passwordSelector = loginFormSelector + ' input[name="login[password]"]',
                loginForm = $(loginFormSelector);

            loginForm.validation();

            return !!$(passwordSelector).valid();
        },

        /**
         * Validate form on submit (if email has not been focused yet).
         */
        validateForm: function () {
            var validPassword = this.validatePassword(),
                validEmail = this.validateEmail(false, true);

            return validPassword && validEmail;
        }
    });
});
