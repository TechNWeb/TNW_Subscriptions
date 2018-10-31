/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'uiComponent',
    'jquery',
    'TNW_Subscriptions/js/ui/model/step-navigator',
    'uiRegistry',
    'Magento_Ui/js/model/messageList'
], function (uiComponent, $, stepNavigator, registry, globalMessageList) {
    'use strict';

    return uiComponent.extend({
        defaults: {
            urlAddEmailToSession: '',
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
         * @param {Boolean} fromForm.
         *
         * @returns {Boolean} - validation result.
         */
        validateEmail: function (focused, fromForm) {
            var loginFormSelector = 'form[data-role=email-for-register]',
                usernameSelector = loginFormSelector + ' input[name=username]',
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

        /**
         * Validate form on submit (if email has not been focused yet).
         */
        validate: function () {
            if (this.validateEmail(false, true)) {
                var loginFormSelector = 'form[data-role=email-for-register]',
                    usernameSelector = loginFormSelector + ' input[name=username]',
                    email = $(usernameSelector).val();

               this.sendAjaxAddEmailToSession(email);
            }
        },

        /**
         * Send ajax to add Customer email to session.
         */
        sendAjaxAddEmailToSession: function (email) {
            var url = this.urlAddEmailToSession;
            $.ajax({
                showLoader: true,
                url: url,
                data: {
                    form_key: window.FORM_KEY,
                    'customer_email': email
                },
                type: "POST",
                dataType: 'json'
            }).done(function (data) {
                if (!data.error) {
                    stepNavigator.navigateNext();
                } else if (data.error_message) {
                    globalMessageList.addErrorMessage({'message' : data.error_message});
                }
            });
        }
    });
});
