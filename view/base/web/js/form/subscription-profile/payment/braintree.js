/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'jquery',
    'mage/translate',
    'TNW_Subscriptions/js/form/subscription-profile/payment/base',
    'uiRegistry',
    'Magento_Braintree/js/validator',
    'underscore',
    'Magento_Ui/js/lib/spinner'
], function ($, $t, PaymentBase, registry, validator, _) {
    'use strict';

    return PaymentBase.extend({
        defaults: {
            scriptLoaded: false,
            braintree: {
                client: null,
                hostedFields: null
            },
            braintreeClient: null,
            hostedFieldsInstance: null,
            grandTotal: null,
            selectedCardType: null,
            selector: 'co-transparent-form-braintree',
            sdkUrl: null,
            hostedFieldsSdkUrl: null,
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

            if (!this.validateHostedFields()) return;

            $('body').trigger('processStart');
            this.hostedFieldsInstance.tokenize(function (tokenizeErr, payload) {
                if (tokenizeErr) {
                    self.processErrors([tokenizeErr.message]);
                    $('body').trigger('processStop');
                    return false;
                }
                var form = registry.get('index = '+self.options.formName);
                form.source.data.payment.braintree.nonce = payload.nonce;
                form.triggerSave([]);
            });
        },

        /**
         * Validate Braintree hosted fields via SDK state api
         * @returns {boolean}
         */
        validateHostedFields: function() {
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
            require([this.sdkUrl, this.hostedFieldsSdkUrl], function (braintreeClient, hostedFields) {
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
                return self.braintree.hostedFields.create(options);
            }).then(function (hostedFieldsInstance) {
                self.hostedFieldsInstance = hostedFieldsInstance;
                self.fieldEventHandler(hostedFieldsInstance);
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
