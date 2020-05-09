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
            stripeClient: null,
            grandTotal: null,
            selectedCardType: null,
            selector: 'co-transparent-form-stripe',
            sdkUrl: null,
            clientToken: null
        },
        initObservable: function () {
            this._super()
                .observe([
                    'selectedCardType'
                ]);

            validator.setConfig(this);
            return this;
        },
        changeVisibility: function(checkBoxChecked) {
            var self = this;
            if (checkBoxChecked && !this.clientToken) {
                this.processErrors([$t('This payment is not available')]);
                return;
            }
        },
        beforeSubmit: function () {
            var self = this;
            $('body').trigger('processStart');

            $.when($.fn.createToken()).done(function (result) {
                if (result.source.id.length) {
                    var cc_last = result.source.card.last4;
                    var exp_month = result.source.card.exp_month;
                    var exp_year = result.source.card.exp_year;
                    var brand = result.source.card.brand;
                    var status = result.source.status;
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
                    self.source.set(self.dataScope + '.client_secret', result.source.id);
                    var form = registry.get('index = '+ self.options.formName);

                    form.triggerSave([]);
                } else {
                    self.set('payment_errors', ['Could not save card.']);
                }

            }).fail(function (result) {
                self.set('payment_errors', [result]);
            });
            $('body').trigger('processStop');
        },

        getSelector: function (field) {
            return '#' + this.code + '-' + field;
        }
    });
});
