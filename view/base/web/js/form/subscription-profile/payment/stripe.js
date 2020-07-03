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
            stripeClient: null,
            grandTotal: null,
            selectedCardType: null,
            selector: 'co-transparent-form-stripe',
            sdkUrl: null,
            clientToken: null
        },

        /**
         * Load Stripe js API
         */
        loadScript: function() {
            var self = this;

            $('body').trigger('processStart');
            require([this.sdkUrl], function () {
                self.stripe.client = window.Stripe(self.stripe.publishableKey);
                self.initStripe();
                $('body').trigger('processStop');
                self.scriptLoaded = true;
            });
        },

        /**
         * Init hosted fields
         */
        initStripe: function() {
            var self = this,
                stripeCardElement;

            try {
                stripeCardElement = self.stripe.client.elements();

                var style = {
                    base: {
                        fontSize: '17px',
                        height: '100%'
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

            validator.setConfig(this);
            return this;
        },

        changeVisibility: function(checkBoxChecked) {
            if (checkBoxChecked && !this.clientToken) {
                this.processErrors([$t('This payment is not available')]);
                return;
            }
            if (checkBoxChecked && !this.scriptLoaded) {
                this.loadScript();
            }
        },

        /**
         * Convert card information to stripe token
         */
        createToken: function () {
            var self = this;

            var defer = $.Deferred();

            self.stripe.client.createSource(self.stripeCardNumber).then(function (response) {
                if (response.error) {
                    defer.reject(response.error.message);
                } else {
                    defer.resolve(response);
                }
            });

            return defer.promise();
        },

        beforeSubmit: function () {
            var self = this;
            $('body').trigger('processStart');

            $.when(this.createToken()).done(function (result) {
                if (result.source.id.length) {
                    var cc_last = result.source.card.last4;
                    var exp_month = result.source.card.exp_month;
                    var exp_year = result.source.card.exp_year;
                    var brand = result.source.card.brand;
                    self.source.set(
                        self.dataScope + '.cc_last_4',
                        cc_last
                    );
                    self.source.set(self.dataScope + '.additional.cc_type', brand);
                    self.source.set(self.dataScope + '.additional.cc_number', 'XXXX');
                    self.source.set(self.dataScope + '.additional.cc_cid', 'XXX');
                    self.source.set(self.dataScope + '.additional.cc_exp_month', exp_month);
                    self.source.set(self.dataScope + '.additional.cc_exp_year', exp_year);
                    self.source.set(self.dataScope + '.cc_last_4', cc_last);
                    self.source.set(self.dataScope + '.cc_token', result.source.id);
                    var form = registry.get('index = '+ self.options.formName);

                    form.triggerSave([]);
                } else {
                    self.set('payment_errors', ['Could not save card.']);
                }
                $('body').trigger('processStop');

            }).fail(function (result) {
                self.set('payment_errors', [result]);
                $('body').trigger('processStop');

            });
        },

        getSelector: function (field) {
            return '[data-container="'+this.code + '-' + field+'"]';
        },
    });
});
