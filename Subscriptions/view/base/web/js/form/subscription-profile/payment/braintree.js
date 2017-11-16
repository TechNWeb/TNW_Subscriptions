/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/form/components/fieldset',
    'uiRegistry',
    'Magento_Ui/js/lib/spinner',
    'jquery/ui'
], function ($, $t, fieldset, registry) {
    'use strict';

    return fieldset.extend({
        defaults: {
            template: 'TNW_Subscriptions/form/subscription-profile/payment/fieldset',
            payment_errors: null,
            iframeSrc: null,
            scriptLoaded: false,
            checked: false,
            braintree: null,
            selectedCardType: null,
            selector: 'co-transparent-form-braintree'
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
         */
        saveBilling: function (value) {
            var form,
                temp = {},
                postData = [];

            if (value){
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
                        this.hideLoader();
                    },
                    complete: function () {
                        this.hideLoader();
                    }
                });
            }
        },

        /**
         * Before submit action for payment method.
         */
        beforeSubmit: function () {
            $('#braintree_submit').trigger('click');
        },

        /**
         * Load external Braintree SDK
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
         */
        initBraintree: function () {
            var self = this;

            try {
                this.showLoader();

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
                     * @param {Object} response
                     */
                    onPaymentMethodReceived: function (response) {
                        console.log(response);
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
            var fields = {
                    number: {
                        selector: '#card-number',
                        placeholder: $t('Credit card number')
                    },
                    expirationMonth: {
                        selector: '#expiration-month',
                        placeholder: $t('MM')
                    },
                    expirationYear: {
                        selector: '#expiration-year',
                        placeholder: $t('YY')
                    }
                };

            if (this.useCvv) {
                fields.cvv = {
                    selector: '#cvv',
                    placeholder: $t('CVV')
                };
            }

            return fields;
        },

        /**
         * Processing errors
         */
        processErrors: function (errors) {
            this.set('payment_errors', [errors]);
        },

        /**
         * Resets payment errors.
         */
        resetErrors:function () {
            this.set('payment_errors', '');
        },

        /**
         * Shows form loader.
         */
        hideLoader: function () {
            $('body').trigger('processStop');
        },

        /**
         * Hides form loader.
         */
        showLoader: function () {
            $('body').trigger('processStart');
        }
    });
});