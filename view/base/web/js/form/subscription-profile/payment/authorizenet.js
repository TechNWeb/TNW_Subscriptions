/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'jquery',
    'mage/translate',
    'TNW_Subscriptions/js/form/subscription-profile/payment/base',
    'uiRegistry',
    'underscore',
    'Magento_Ui/js/lib/spinner'
], function ($, $t, PaymentBase, registry, _) {
    'use strict';

    return PaymentBase.extend({
        defaults: {
            code: 'tnw_authorize_cim',
            acceptConfig: null,
            accept: null,
            payment_errors: null
        },

        /**
         * Init configuration
         * Load Accept.js component
         */
        initConfig: function () {
            var self = this;
            this._super();
            require([self.acceptConfig.sdkUrl], function () {
                self.accept = window.Accept;
            });
        },

        /**
         * Before submit action for payment method.
         * @return void
         */
        beforeSubmit: function () {
            var self = this;
            $('body').trigger('processStart');
            this.validate()
                .done(function (opaqueData) {
                    var form = registry.get('index = '+self.options.formName);
                    self.source.set(self.dataScope+'.opaqueDescriptor', opaqueData.dataDescriptor);
                    self.source.set(self.dataScope+'.opaqueValue', opaqueData.dataValue);
                    self.source.set(
                        self.dataScope+'.cc_last_4',
                        self.source.get(self.dataScope+'.additional.cc_number').substr(-4)
                    );
                    self.source.set(self.dataScope+'.additional.cc_number', 'XXXX');
                    self.source.set(self.dataScope+'.additional.cc_cid', 'XXX');
                    $('body').trigger('processStop');
                    form.triggerSave([]);
                })
                .fail(function (errors) {
                    $('body').trigger('processStop');
                    self.set('payment_errors', [errors]);
                });
        },

        /**
         * Validate paymentData via api
         * @returns {jQuery.Deferred}
         */
        validate: function () {
            var state = $.Deferred(),
                paymentData = {
                    cardData: {
                        cardNumber: $(this.getSelector('cc-number')).val().replace(/\D/g, ''),
                        month: $(this.getSelector('cc-month')).val(),
                        year: $(this.getSelector('cc-year')).val(),
                        cardCode: $(this.getSelector('cc-cvv')).val()
                    },
                    authData: {
                        clientKey: this.acceptConfig.clientKey,
                        apiLoginID: this.acceptConfig.apiLoginID
                    }
                };

            this.accept.dispatchData(paymentData, function (response) {
                if (response.messages.resultCode === "Error") {
                    var messages = $.map(response.messages.message, function(message) {
                        return message.code + ": " + message.text;
                    });
                    state.reject(messages.join(' '));
                } else {
                    state.resolve(response.opaqueData);
                }
            });

            return state.promise();
        },

        /**
         * Get payment method code
         *
         * @returns {string}
         */
        getCode: function () {
            return this.code;
        },

        /**
         * Get jQuery selector
         * @param {String} field
         * @returns {String}
         */
        getSelector: function (field) {
            return '[data-container="'+this.code + '-' + field+'"]';
        },
    });
});
