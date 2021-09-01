/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'uiElement',
    'jquery',
    'mage/translate',
    'TNW_Subscriptions/js/form/subscription-profile/payment/braintree-3d-secure',
    'Magento_Ui/js/modal/alert'
], function (Element, $, $t, verify3DSecure, alert) {
    return Element.extend({
        defaults: {
            template: 'TNW_Subscriptions/rebill/queue-process-braintree',
            braintree: {
                client: null
            },
            nonceUrl: null,
            publicHash: null,
            clientToken: null,
            paymentMethodNonce: null,
            braintreeClientInstance: null,
            processUrl: null
        },

        initialize: function () {
            this._super()
            this.loadScript()

        },

        processRebill: function () {
            var self = this;

            $('body').trigger('processStart')

            $.getJSON(self.nonceUrl, {
                'public_hash': self.publicHash
            }).done(function (response) {
                self.paymentMethodNonce = response.paymentMethodNonce;
                self.validate3DSecure(self)
            })
        },

        validate3DSecure: function () {
            var self = this;

            verify3DSecure.setConfig({
                'braintree' : self.braintree,
                'useCvvVault' : self.useCvvVault,
                'totalAmount' : self.totalAmount,
                'thresholdAmount' : self.thresholdAmount,
                'specificCountries' : self.specificCountries
            });
            $.when(verify3DSecure.validate(self))
            .done(function (nonce) {
                $.post(self.processUrl, {
                    formKey: $('input[name=form_key]').val(),
                    paymentMethodNonce: nonce
                }).done(function (response) {
                    if (!response.error) {
                        $('.tnw-queue-process-wrapper').remove();
                    }
                    // self.processMessages(response.messages.join(), response.error ? 'error' : 'success')
                }).fail(function () {
                    self.processErrors($t('Something went wrong.'))
                }).always(function () {
                    $('body').trigger('processStop')
                })
            })
            .fail(function (errorMessage) {
                self.processErrors(errorMessage)
                $('body').trigger('processStop')
            });
        },

        /**
         * Load external Braintree SDK
         * @return void
         */
        loadScript: function () {
            var self = this;

            require(['braintree'], function (braintreeClient) {
                self.braintree.client = braintreeClient
                self.initBraintree()
            });
        },

        /**
         * Setup Braintree SDK
         * @return void
         */
        initBraintree: function () {
            var self = this;

            $('body').trigger('processStart')

            this.braintree.client.create({
                authorization: this.clientToken
            }).then(function (clientInstance) {
                self.braintreeClientInstance = clientInstance
                $('body').trigger('processStop')
            }).catch(function () {
                $('body').trigger('processStop')
                self.processErrors($t('Braintree can\'t be initialized.'))
            })
        },

        processErrors: function (msg) {
            alert({
                title: $t('Error'),
                content: msg
            })
        }
    })
})
