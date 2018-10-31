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
    'Magento_Ui/js/lib/spinner'
], function ($, $t, PaymentBase, registry, validator) {
    'use strict';

    return PaymentBase.extend({
        defaults: {
            scriptLoaded: false,
            braintree: null,
            grandTotal: null,
            braintreeClient: null,
            selectedCardType: null,
            selector: 'co-transparent-form-braintree',
            sdkUrl: null,
            clientToken: null,
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
            $('body').trigger('processStart');
            $('#braintree_submit').trigger('click');
        },

        /**
         * Load external Braintree SDK
         * @return void
         */
        loadScript: function () {
            var self = this,
                state = self.scriptLoaded;

            this.showLoader();
            require([this.sdkUrl], function (braintree) {
                state(true);
                self.braintree = braintree;
                self.initBraintree();
                self.hideLoader();
            });
        },

        /**
         * Setup Braintree SDK
         * @return void
         */
        initBraintree: function () {
            var self = this;

            try {
                $('body').trigger('processStart');

                this.braintreeClient = new this.braintree.api.Client({
                    clientToken: this.clientToken
                });

                this.braintree.setup(this.clientToken, 'custom', {
                    id: this.selector,
                    hostedFields: this.getHostedFields(),

                    /**
                     * Triggered when sdk was loaded
                     */
                    onReady: function () {
                        $('body').trigger('processStop');
                    },

                    /**
                     * Callback for success response
                     */
                    onPaymentMethodReceived: function (response) {
                        $('body').trigger('processStop');

                        if (!self.validateCardType()) {
                            return;
                        }

                        var form = registry.get('index = '+self.options.formName);
                        form.source.data.payment.braintree.nonce = response.nonce;
                        form.triggerSave([]);
                    },

                    /**
                     * Error callback
                     * @param {Object} response
                     */
                    onError: function (response) {
                        self.processErrors(response.message);
                        $('body').trigger('processStop');
                    }
                });
            } catch (e) {
                $('body').trigger('processStop');
                this.processErrors(e.message);
            }
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

                    /**
                     * Triggered when hosted field is changed
                     * @param {Object} event
                     */
                    onFieldEvent: function (event) {
                        return self.fieldEventHandler(event);
                    }
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
         * @param {Object} event
         * @returns {Boolean}
         */
        fieldEventHandler: function (event) {
            if (event.type !== 'fieldStateChange') {
                return false;
            }

            // Handle a change in validation or card type
            if (event.target.fieldKey === 'number') {
                this.selectedCardType(null);
            }

            if (event.card) {
                this.selectedCardType(validator.getMageCardType(event.card.type, this.getCcAvailableTypes()));
            }
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
