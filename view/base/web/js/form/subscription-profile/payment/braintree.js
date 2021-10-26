/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'jquery',
    'mage/translate',
    'TNW_Subscriptions/js/form/subscription-profile/payment/base',
    'uiRegistry',
    'PayPal_Braintree/js/validator',
    'underscore',
    'TNW_Subscriptions/js/form/subscription-profile/payment/braintree-3d-secure',
    'Magento_Ui/js/lib/spinner'
], function ($, $t, PaymentBase, registry, validator, _, verify3DSecure) {
    'use strict';

    return PaymentBase.extend({
        defaults: {
            scriptLoaded: false,
            braintree: {
                client: null,
                hostedFields: null
            },
            nonceUrl: null,
            braintreeClientInstance: null,
            hostedFieldsInstance: null,
            paymentMethodNonce: null,
            grandTotal: null,
            selectedCardType: null,
            selector: 'co-transparent-form-braintree',
            clientToken: null,
            selectorsMapper: {
                'expirationMonth': 'cc-month',
                'expirationYear': 'cc-year',
                'number': 'cc-number',
                'cvv': 'cc-cvv'
            },
            useCvv: true,
            links: {
                selectedCardType: 'dataContainer = braintree-cc-type:value'
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

            return this;
        },

        /**
         * Change fieldset visibility and clear child elems values if fieldset was hidden
         *
         * @param {boolean} checkBoxChecked
         * @return void
         */
        changeVisibility: function (checkBoxChecked) {
            if (checkBoxChecked && !this.clientToken) {
                this.processErrors([$t('This payment is not available')]);
                return;
            }

            if (checkBoxChecked && !this.scriptLoaded()) {
                this.loadScript();
            }
            validator.setConfig(this);
        },

        /**
         * Before submit action for payment method.
         * @return void
         */
        beforeSubmit: function () {
            var self = this,
                form = registry.get('index = ' + self.options.formName);

            $('body').trigger('processStart');

            if (this.index === 'braintree_cc_vault') {
                if (this.three_d_enabled) {
                    $.getJSON(self.nonceUrl, {
                        'public_hash': self.source.get(self.dataScope + '.additional.publicHash')
                    }).done(function (response) {
                        self.paymentMethodNonce = response.paymentMethodNonce;
                        self.validate3DSecure()
                    })
                    return
                }
                registry.get('index = '+this.options.formName).triggerSave([]);
                return
            }

            if (this.index === 'braintree' && !this.validateHostedFields()) {
                return
            }

            this.hostedFieldsInstance.tokenize(function (tokenizeErr, payload) {
                if (tokenizeErr) {
                    self.processErrors([tokenizeErr.message]);
                    $('body').trigger('processStop');
                    return false;
                }
                self.paymentMethodNonce = payload.nonce;
                if (self.three_d_enabled) {
                    self.validate3DSecure();
                } else {
                    form.source.data.payment.braintree.nonce = payload.nonce;
                    form.triggerSave([]);
                }
            });
        },

        validate3DSecure: function () {
            var self = this,
                form = registry.get('index = ' + self.options.formName);

            verify3DSecure.setConfig({
                'braintree' : self.braintree,
                'useCvvVault' : self.useCvvVault,
                'totalAmount' : self.totalAmount,
                'thresholdAmount' : self.thresholdAmount,
                'specificCountries' : self.specificCountries
            });
            $.when(verify3DSecure.validate(self))
            .done(function (nonce) {
                self.paymentMethodNonce = form.source.data.payment[self.index].nonce = nonce
                    ? nonce
                    : self.paymentMethodNonce;
                form.triggerSave([]);
            })
            .fail(function (errorMessage) {
                self.processErrors([errorMessage]);
                $('body').trigger('processStop');
                return false;
            });
        },

        /**
         * Validate Braintree hosted fields via SDK state api
         * @returns {boolean}
         */
        validateHostedFields: function () {
            var self = this,
                state = this.hostedFieldsInstance.getState(),
                formValid = Object.keys(state.fields).every(function (key) {
                    return state.fields[key].isValid;
                });

            if (formValid) return true;

            _.each(Object.keys(state.fields), function (fieldKey) {
                if (fieldKey in self.selectorsMapper && state.fields[fieldKey].isValid === false) {
                    self.addInvalidClass(self.selectorsMapper[fieldKey]);
                }
            });

            this.processErrors([$t('Please correct the problems with the Credit Card fields.')]);
            $('body').trigger('processStop');
            return false;
        },

        /**
         * Load external Braintree SDK
         * @return void
         */
        loadScript: function () {
            var self = this,
                state = self.scriptLoaded;

            $('body').trigger('processStart');
            require(['braintree', 'braintreeHostedFields'], function (braintreeClient, hostedFields) {
                state(true);
                self.braintree.client = braintreeClient;
                self.braintree.hostedFields = hostedFields;
                self.initBraintree();
                $('body').trigger('processStop');
            });
        },

        /**
         * Setup Braintree SDK
         * @return void
         */
        initBraintree: function () {
            var self = this;

            $('body').trigger('processStart');

            this.braintree.client.create({
                authorization: this.clientToken
            }).then(function (clientInstance) {
                var options = {
                    client: clientInstance,
                    fields: self.getHostedFields()
                };
                self.braintreeClientInstance = clientInstance;
                if (self.index === 'braintree') {
                    return self.braintree.hostedFields.create(options);
                }
                return false;
            }).then(function (hostedFieldsInstance) {
                if (hostedFieldsInstance) {
                    self.hostedFieldsInstance = hostedFieldsInstance;
                    self.fieldEventHandler(hostedFieldsInstance);
                }
                $('body').trigger('processStop');
            }).catch(function () {
                $('body').trigger('processStop');
                self.processErrors([$t('Braintree can\'t be initialized.')]);
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
        },

        /**
         * Add invalid class to field.
         *
         * @param {String} field
         * @returns void
         * @private
         */
        addInvalidClass: function (field) {
            $(this.getSelector(field)).addClass('braintree-hosted-fields-invalid');
        },

        /**
         * Remove invalid class from field.
         *
         * @param {String} field
         * @returns void
         * @private
         */
        removeInvalidClass: function (field) {
            $(this.getSelector(field)).removeClass('braintree-hosted-fields-invalid');
        }
    });
});
