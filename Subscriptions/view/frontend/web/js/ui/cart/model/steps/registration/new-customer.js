/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'uiComponent',
    'jquery',
    'TNW_Subscriptions/js/ui/model/step-navigator'
], function (uiComponent, $, stepNavigator) {
    'use strict';

    return uiComponent.extend({
        defaults: {
            placeholderEmail: $.mage.__('Email Address'),
            emailFocused: false,
            email: '',
            listens: {
                emailFocused: 'validateEmail'
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
         *
         * @returns {Boolean} - validation result.
         */
        validateEmail: function (focused) {
            var loginFormSelector = 'form[data-role=email-for-register]',
                usernameSelector = loginFormSelector + ' input[name=username]',
                loginForm = $(loginFormSelector),
                validator;

            loginForm.validation();

            if (focused === false && !!this.email()) {
                return !!$(usernameSelector).valid();
            }

            validator = loginForm.validate();
            debugger;
            return validator.check(usernameSelector);
        },

        /**
         * Validate form on submit (if email has not been focused yet).
         */
        validate: function () {
            if (this.validateEmail(false)) {
                stepNavigator.navigateNext();
            }
        }
    });
});
