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
            stripe: {
                client: null,
                publishableKey: null
            },
            scriptLoaded: null,
            scriptLoading: null,
            stripeClient: null,
            grandTotal: null,
            selectedCardType: null,
            selector: 'co-transparent-form-stripe',
            sdkUrl: null,
            createUrl: null,
            clientToken: null,
            currencyCode: null,
            totalAmount: null
        },

        initialize: function () {
            this._super();
            if (this.index === 'tnw_stripe_vault') {
                this.loadScript()
            }
            return this;
        },

        /**
         * Load Stripe js API
         */
        loadScript: function () {
            var self = this;

            if (self.scriptLoaded || self.scriptLoading) {
                return;
            }
            self.scriptLoading = true;
            $('body').trigger('processStart');
            require([this.sdkUrl], function () {
                self.stripe.client = window.Stripe(self.stripe.publishableKey);
                if (self.index === 'tnw_stripe') {
                    self.initStripe();
                }
                $('body').trigger('processStop');
                self.scriptLoaded = true;
            });
        },

        /**
         * Init hosted fields
         */
        initStripe: function () {
            var self = this,
                stripeCardElement;

            try {
                stripeCardElement = self.stripe.client.elements();

                var style = {
                    base: {
                        fontSize: '17px'
                    }
                };

                self.stripeCardNumber = stripeCardElement.create('cardNumber', {style: style});
                self.stripeCardNumber.mount(this.getSelector('cc_number'));
                self.stripeCardNumber.on('change', function (event) {
                    if (event.empty === false) {
                        self.validateCardType();
                    }

                    self.selectedCardType(
                        validator.getMageCardType(event.brand, self.availableCardTypes)
                    );
                });

                stripeCardElement
                .create('cardExpiry', {style: style})
                .mount(this.getSelector('cc_exp'));

                stripeCardElement
                .create('cardCvc', {style: style})
                .mount(this.getSelector('cc_cid'));
            } catch (e) {
                self.error(e.message);
            }
        },

        /**
         * Validate current entered card type
         * @returns {Boolean}
         */
        validateCardType: function () {
            var $input = $(this.getSelector('cc_number'));
            $input.removeClass('stripe-shosted-fields-invalid');

            if (!this.selectedCardType()) {
                $input.addClass('stripe-shosted-fields-invalid');
                return false;
            }
            this.source.set(this.dataScope + '.additional.cc_type', this.selectedCardType());
            return true;
        },

        initObservable: function () {
            this._super()
                .observe('selectedCardType');

            if (this.index === 'tnw_stripe') {
                validator.setConfig(this);
            }
            return this;
        },

        changeVisibility: function (checkBoxChecked) {
            debugger
            if (checkBoxChecked && !this.clientToken) {
                this.processErrors([$t('This payment is not available')]);
                return;
            }
            if (checkBoxChecked && !this.scriptLoaded) {
                this.loadScript();
            }
        },

        /**
         * Create stripe api payment method
         * @returns promise
         */
        createPaymentMethod: function () {
            var self = this,
                defer = $.Deferred();

            self.stripe.client.createPaymentMethod({
                type: 'card',
                card: self.stripeCardNumber,
                billing_details: self.getOwnerData()
            }).then(function (response) {
                if (response.error) {
                    defer.reject(response.error.message);
                } else {
                    defer.resolve(response);
                }
            });

            return defer.promise();
        },

        /**
         * Create payment intent
         * @returns {jQuery.Deferred}
         */
        createPaymentIntent: function () {
            this.isCreatingPaymentIntent = true;
            var self = this,
                dfd = $.Deferred();

            $.post(
                self.createUrl,
                {data: JSON.stringify(arguments[0])}
            ).then(function (response) {
                if (response.error) {
                    self.error(response.error.message);
                    dfd.reject(response);
                } else {
                    dfd.resolve(response);
                }
            }).always(function () {
                self.isCreatingPaymentIntent = false;
            });
            return dfd;
        },

        beforeSubmit: function () {
            var self = this,
                form = registry.get('index = '+ self.options.formName);
            $('body').trigger('processStart');

            if (this.index === 'tnw_stripe_vault') {
                this.createPaymentIntent({
                    'public_hash': this.source.get(this.dataScope + '.additional.publicHash'),
                    'amount' : this.totalAmount
                }).done(this.processPaymentIntent.bind(this)).fail(function () {
                    $('body').trigger('processStop');
                    self.set('payment_errors', ['Something went wrong.']);
                });
                return;
            }
            $.when(this.createPaymentMethod()).done(function (result) {
                if (result.paymentMethod.id.length) {
                    var cc_last = result.paymentMethod.card.last4,
                        exp_month = result.paymentMethod.card.exp_month,
                        exp_year = result.paymentMethod.card.exp_year,
                        brand = result.paymentMethod.card.brand;

                    self.source.set(self.dataScope + '.additional.cc_type', brand);
                    self.source.set(self.dataScope + '.additional.cc_number', 'XXXX');
                    self.source.set(self.dataScope + '.additional.cc_cid', 'XXX');
                    self.source.set(self.dataScope + '.additional.cc_exp_month', exp_month);
                    self.source.set(self.dataScope + '.additional.cc_exp_year', exp_year);
                    self.source.set(self.dataScope + '.cc_last_4', cc_last);
                    self.source.set(self.dataScope + '.paymentMethod', JSON.stringify(result.paymentMethod));
                } else {
                    self.set('payment_errors', ['Could not save card.']);
                    return;
                }
                if (!result.paymentMethod.card.three_d_secure_usage.supported) {
                    form.triggerSave([]);
                    return;
                }

                self.createPaymentIntent({
                    paymentMethod: result.paymentMethod,
                    amount: self.totalAmount,
                    currency: self.currencyCode
                }).done(self.processPaymentIntent.bind(self)).fail(function () {
                    $('body').trigger('processStop');
                    self.set('payment_errors', ['Something went wrong.']);
                });

            }).fail(function (result) {
                self.set('payment_errors', [result]);
                $('body').trigger('processStop');
            });
        },

        processPaymentIntent: function (response) {
            var form = registry.get('index = '+ this.options.formName),
                self = this;

            if (response.skip_3ds) {
                $('body').trigger('processStop');
                this.source.set(this.dataScope + '.paymentMethod', JSON.stringify(response.paymentIntent));
                form.triggerSave([]);
                return;
            }
            // Disable Payment Token
            if (!response.pi) {
                $('body').trigger('processStop');
                return;
            }
            this.authenticateCustomer(response.pi, function (error, response) {
                if (error) {
                    self.set('payment_errors', ['3D Secure authentication failed.']);
                } else {
                    self.source.set(self.dataScope + '.paymentMethod', JSON.stringify(response.paymentIntent));
                    form.triggerSave([]);
                }
                $('body').trigger('processStop');
            });
        },

        /**
         * Authenticate customer
         * @param paymentIntentId
         * @param done
         */
        authenticateCustomer: function (paymentIntentId, done) {
            var self = this
            try {
                this.stripe.client.retrievePaymentIntent.apply(this.stripe.client, [paymentIntentId]).then(function (result) {
                    if (result.error) {
                        return done(result.error, result);
                    }
                    if (result.paymentIntent.status === "requires_action"
                        || result.paymentIntent.status === "requires_source_action") {
                        return self.handleCardAction(paymentIntentId, done);
                    }
                    return done(false, result);
                });
            } catch (e) {
                done(e.message);
            }
        },

        /**
         * Get customer billing address details
         * @returns {{address: {country: string, city: string, line1: string}, name: string}}
         */
        getOwnerData: function () {
            var provider,
                stripeData

            provider = registry.get('index = tnw_subscriptionprofile_create_shipping_and_billing_form_data_source')
                ? registry.get('index = tnw_subscriptionprofile_create_shipping_and_billing_form_data_source')
                : registry.get('index = tnw_subscriptionprofile_summary_billing_address_form_data_source')

            if (provider) {
                stripeData = {
                    name: provider.get('data.billing_info.firstname') + ' ' + provider.get('data.billing_info.lastname'),
                    address: {
                        country: provider.get('data.billing_address.country_id'),
                        line1: provider.get('data.billing_address.street0'),
                        city: provider.get('data.billing_address.city')
                    }
                };

                if (provider.get('data.billing_address.street1')) {
                    stripeData.address.line2 = provider.get('data.billing_address.street1');
                }

                if (provider.get('data.billing_address.postcode')) {
                    stripeData.address.postal_code = provider.get('data.billing_address.postcode');
                }

                if (provider.get('data.billing_address.region_id')) {
                    stripeData.address.state = provider.get('data.billing_address.region_id');
                }
            } else {
                stripeData = {
                    name: $('#firstname').val() + ' ' + $('#lastname').val(),
                    address: {
                        country: $('#country_id').val(),
                        line1: $('#street_1').val(),
                        city: $('#city').val()
                    }
                };
                if ($('#street_2').length) {
                    stripeData.address.line2 = $('#street1').val();
                }
                if ($('#zip:not(:disabled)').length) {
                    stripeData.address.postal_code = $('#zip').val();
                }
                if ($('#region_id:not(:disabled)').length) {
                    stripeData.address.state = $('#region_id').val();
                }
            }

            return stripeData;
        },

        /**
         * Run 3DS check
         * @param paymentIntentId
         * @param done
         */
        handleCardAction: function (paymentIntentId, done) {
            try {
                this.stripe.client.handleCardAction.apply(this.stripe.client, [paymentIntentId]).then(function (result) {
                    if (result.error) {
                        return done(result.error.message, result);
                    }
                    return done(false, result);
                });
            } catch (e) {
                done(e.message);
            }
        },

        getSelector: function (field) {
            return '[data-container="'+this.code + '-' + field+'"]';
        },
    });
});
