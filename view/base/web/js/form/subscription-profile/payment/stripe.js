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
                selectedCardType: 'dataContainer = tnw_stripe-cc-type:value'
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
            var self = this;
            if (checkBoxChecked && !this.clientToken) {
                this.processErrors([$t('This payment is not available')]);
                return;
            }

            if (checkBoxChecked && !this.scriptLoaded()) {
                //this.loadScript();
            }
        },
        /**
         * Before submit action for payment method.
         * @return void
         */
        beforeSubmit: function () {
            var self = this;
            $('body').trigger('processStart');

            /*var form = registry.get('index = '+self.options.formName);
            console.log(self.options.formName);
            self.source.set(
                self.dataScope+'.cc_last_4',
                self.source.get(self.dataScope+'.additional.cc_number').substr(-4)
            );
            self.source.set(self.dataScope+'.additional.cc_number', 'XXXX');
            self.source.set(self.dataScope+'.additional.cc_cid', 'XXX');
            $('body').trigger('processStop');
            form.triggerSave([]);*/
            //TODO Actually run validation through Stripe Api
            this.validate()
                .done(function (result) {
                    var form = registry.get('index = '+self.options.formName);
                    /*self.source.set(
                        self.dataScope+'.cc_last_4',
                        self.source.get(self.dataScope+'.additional.cc_number').substr(-4)
                    );
                    self.source.set(self.dataScope+'.additional.cc_number', 'XXXX');
                    self.source.set(self.dataScope+'.additional.cc_cid', 'XXX');*/
                    $('body').trigger('processStop');
                    form.triggerSave([]);
                })
                .fail(function (errors) {
                    $('body').trigger('processStop');
                    self.set('payment_errors', [errors]);
                });
        },

        /**
         * Load external Stripe SDK
         * @return void
         */
        loadScript: function () {
            console.log('script loaded');
            var self = this,
                state = self.scriptLoaded;

            self.showLoader();
            //$('body').trigger('processStart');
            require([this.sdkUrl], function (stripeClient) {
                state(true);
                self.stripe.client = window.Stripe(self.publishableKey);
                self.stripe.publishableKey = self.publishableKey;

                self.initStripeFields();

                //$('body').trigger('processStop');
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
            return '#' + this.code + '-' + field;
        },


        /**
         * Validate paymentData via api
         * @returns {jQuery.Deferred}
         */
        validate: function () {
            var self = this;
            var state = $.Deferred();

            var $input = $(this.getSelector('cc_number'));
            $input.removeClass('stripe-shosted-fields-invalid');

            if (!this.validateCardType()) {
               // state.reject('Card is not valid.');
            }
            //var token = self.stripe.client.createSource(self.stripeCardNumber);
            $.when($.fn.createToken()).done(function () {

            }).fail(function (result) {
                state.reject('Could not validate card.');
            });

            //failed  $(this.getSelector('cc_type')).val(this.selectedCardType());


            state.resolve([]);
            return state.promise();
        },
        /**
         * Convert card information to stripe token
         */
        createToken2: function () {
            var self = this;
            var defer = $.Deferred();

            self.stripe.client.createSource(self.stripeCardNumber).then(function (response) {
                if (response.error) {
                    defer.reject(response.error.message);
                } else {
                    var token = response.source.id;
                    defer.resolve();
                }
            });

            return defer.promise();
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
            var self = this;
            return validator.getMageCardType(event.brand, self.getCcAvailableTypes());
        }
    });
});
