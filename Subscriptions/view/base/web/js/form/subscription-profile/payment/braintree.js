/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/form/components/fieldset',
    'uiRegistry',
    'Magento_Braintree/js/validator',
    'Magento_Ui/js/lib/spinner',
    'jquery/ui'
], function ($, $t, fieldset, registry, validator) {
    'use strict';

    return fieldset.extend({
        defaults: {
            template: 'TNW_Subscriptions/form/subscription-profile/payment/fieldset',
            payment_errors: null,
            iframeSrc: null,
            scriptLoaded: false,
            checked: false,
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
                    'selectedCardType',
                    'checked',
                    'payment_errors'
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
                this.processErrors($.mage.__('This payment is not available'));
                return;
            }

            if (checkBoxChecked && !this.scriptLoaded()) {
                this.loadScript();
            }
        },

        /**
         * Trigger form saving.
         * @param {boolean} value
         * @return void
         */
        saveBilling: function (value) {
            var form,
                temp = {},
                postData = [];

            if (value) {
                form = registry.get('index = ' + this.options.formName);
                this.showLoader();
                this.resetErrors();
                //creating post data, this structure is needed to proper saving
                postData = (typeof FORM_KEY !== 'undefined') ? {'form_key': FORM_KEY} : {};
                temp[this.code] = {
                    method: '1'
                };
                postData.payment = temp;

                $.ajax({
                    url: form.source.process_url,
                    type: 'post',
                    context: this,
                    data: postData,
                    success: function (response) {
                        if (response.error) {
                            this.processErrors(response.error_messages);
                        }
                    },
                    complete: function () {
                        this.hideLoader();
                    }
                });
            }
        },

        /**
         * Before submit action for payment method.
         * @return void
         */
        beforeSubmit: function () {
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
                this.showLoader();

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
                        self.hideLoader();
                    },

                    /**
                     * Callback for success response
                     */
                    onPaymentMethodReceived: function (response) {
                        self.hideLoader();

                        if (!self.validateCardType()) {
                            return;
                        }

                        var form = registry.get('index = '+self.options.formName);
                        self.braintreeClient.verify3DS({
                            amount: self.grandTotal,
                            creditCard: response.nonce,
                            onUserClose: function () {

                            }
                        }, function (error, response) {
                            var liability;

                            if (error) {
                                form.triggerSave([error.message]);
                                return;
                            }

                            liability = {
                                shifted: response.verificationDetails.liabilityShifted,
                                shiftPossible: response.verificationDetails.liabilityShiftPossible
                            };

                            if (liability.shifted || !liability.shifted && !liability.shiftPossible) {
                                self.showLoader();
                                form.source.data.payment.braintree.nonce = response.nonce;
                                form.triggerSave([]);
                            } else {
                                form.triggerSave([$t('Please try again with another form of payment.')]);
                            }
                        });
                    },

                    /**
                     * Error callback
                     * @param {Object} response
                     */
                    onError: function (response) {
                        self.processErrors(response.message);
                        self.hideLoader();
                    }
                });
            } catch (e) {
                this.hideLoader();
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
        },

        /**
         * Processing errors
         * @return void
         */
        processErrors: function (errors) {
            this.set('payment_errors', [errors]);
        },

        /**
         * Resets payment errors.
         * @return void
         */
        resetErrors:function () {
            this.set('payment_errors', '');
        },

        /**
         * Shows form loader.
         * @return void
         */
        hideLoader: function () {
            $('body').trigger('processStop');
        },

        /**
         * Hides form loader.
         * @return void
         */
        showLoader: function () {
            $('body').trigger('processStart');
        }
    });
});
