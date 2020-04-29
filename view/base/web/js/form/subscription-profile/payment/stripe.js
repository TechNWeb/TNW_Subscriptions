/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'jquery',
    'mage/translate',
    'TNW_Subscriptions/js/form/subscription-profile/payment/base',
    'uiRegistry',
    'TNW_Stripe/js/validator',
    'underscore',
    'Magento_Ui/js/lib/spinner'
], function ($, $t, PaymentBase, registry, validator, _) {
    'use strict';

    return PaymentBase.extend({
        defaults: {
            scriptLoaded: false,
            stripe: {
                client: null,
                publishableKey: null
            },
            stripeClient: null,
            hostedFieldsInstance: null,
            grandTotal: null,
            selectedCardType: null,
            selector: 'co-transparent-form-stripe',
            sdkUrl: null,
            clientToken: null,
            selectorsMapper: {
                'expirationMonth': 'cc-month',
                'expirationYear': 'cc-year',
                'number': 'cc-number',
                'cvv': 'cc-cvv'
            },
            useCvv: true,
            links: {
                selectedCardType: 'dataContainer = stripe-cc-type:value'
            }
        },

        /**
         * Set list of observable attributes
         * @returns {exports.initObservable}
         */
        initObservable: function () {
            this._super()
                .observe([
                    'scriptLoaded',
                    'selectedCardType'
                ]);

            validator.setConfig(this);
            return this;
        },

        /**
         * Change fieldset visibility and clear child elems values if fieldset was hidden
         *
         * @param {boolean} checkBoxChecked
         * @return void
         */
        changeVisibility: function(checkBoxChecked) {
            if (checkBoxChecked && !this.clientToken) {
                this.processErrors([$t('This payment is not available')]);
                return;
            }

            if (checkBoxChecked && !this.scriptLoaded()) {
                this.loadScript();
            }
        },

        /**
         * Before submit action for payment method.
         * @return void
         */
        beforeSubmit: function () {
            var self = this;
            $('body').trigger('processStart');
            var form = registry.get('index = '+self.options.formName);
            console.log(self.options.formName);
            self.source.set(
                self.dataScope+'.cc_last_4',
                self.source.get(self.dataScope+'.additional.cc_number').substr(-4)
            );
            self.source.set(self.dataScope+'.additional.cc_number', 'XXXX');
            self.source.set(self.dataScope+'.additional.cc_cid', 'XXX');
            $('body').trigger('processStop');
            form.triggerSave([]);
            //TODO Actually run validation through Stripe Api
            /*this.validate()
                .done(function (opaqueData) {
                    var form = registry.get('index = '+self.options.formName);
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
                });*/
        },

        /**
         * Load external Stripe SDK
         * @return void
         */
        loadScript: function () {
            var self = this,
                state = self.scriptLoaded;

            self.showLoader();
            $('body').trigger('processStart');
            require([this.sdkUrl], function (stripeClient) {
                state(true);
                self.stripe.client = stripeClient;
                self.stripe.publishableKey = window.Stripe(self.publishableKey);
                $('body').trigger('processStop');
                self.hideLoader();
            });
        },

        /**
         * Get hosted fields configuration
         * @returns {Object}
         */
        getHostedFields: function () {
            var self = this,

                fields = {
                    number: {
                        selector: self.getSelector('cc-number'),
                        placeholder: $t('Credit card number')
                    },
                    expirationMonth: {
                        selector: self.getSelector('cc-month'),
                        placeholder: $t('MM')
                    },
                    expirationYear: {
                        selector: self.getSelector('cc-year'),
                        placeholder: $t('YY')
                    },
                };
            if (this.useCvv) {
                fields.cvv = {
                    selector: self.getSelector('cc-cvv'),
                    placeholder: $t('CVV')
                };
            }

            return fields;
        },

        /**
         * Get jQuery selector
         * @param {String} field
         * @returns {String}
         */
        getSelector: function (field) {
            return '[data-container="'+this.code + '-' + field+'"]';
        },

        /**
         * Function to handle hosted fields events
         * @returns {Boolean}
         * @param hostedFieldsInstance
         */
        fieldEventHandler: function (hostedFieldsInstance) {
            var self = this;
            hostedFieldsInstance.on('empty', function (event) {
                if (event.emittedBy === 'number') {
                    self.selectedCardType(null);
                }
            });

            hostedFieldsInstance.on('cardTypeChange', function (event) {
                if (event.cards.length !== 1) {
                    return;
                }
                self.selectedCardType(
                    validator.getMageCardType(event.cards[0].type, self.getCcAvailableTypes())
                );
            });

            hostedFieldsInstance.on('validityChange', function (event) {
                var field = event.fields[event.emittedBy],
                    fieldKey = event.emittedBy;

                if (fieldKey in self.selectorsMapper && field.isValid === false) {
                    self.addInvalidClass(self.selectorsMapper[fieldKey]);
                }
            });

            hostedFieldsInstance.on('blur', function (event) {
                if (event.emittedBy === 'number') {
                    self.validateCardType();
                }
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
                        clientKey: this.clientToken
                    }
                };

            /*this.accept.dispatchData(paymentData, function (response) {
                if (response.messages.resultCode === "Error") {
                    var messages = $.map(response.messages.message, function(message) {
                        return message.code + ": " + message.text;
                    });
                    state.reject(messages.join(' '));
                } else {
                    state.resolve(response.opaqueData);
                }
            });*/
            state.resolve([]);
            return state.promise();
        },
        /**
         * Get list of currently available card types
         * @returns {Array}
         */
        getCcAvailableTypes: function () {
            var types = [],
                $options = $(this.getSelector('cc-type')).find('option');

            $.map($options, function (option) {
                types.push($(option).val());
            });

            return types;
        },

        /**
         * Validate current entered card type
         * @returns {Boolean}
         */
        validateCardType: function () {
            return this.selectedCardType();
        }
    });
});
