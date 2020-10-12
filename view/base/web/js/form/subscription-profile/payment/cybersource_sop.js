/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'jquery',
    'mage/translate',
    'TNW_Subscriptions/js/form/subscription-profile/payment/base',
    'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
    'uiRegistry',
    'underscore',
    'mage/template',
    'Magento_Ui/js/lib/spinner'
], function ($, $t, PaymentBase, cardNumberValidator, registry, _, mageTemplate) {
    'use strict';

    return PaymentBase.extend({
        defaults: {
            code: null,
            payment_errors: null,
            ccNumber: null,
            availableCardTypes: null,
            cardFieldsMap: null,
            sopServiceUrl: null,
            loadSilentDataUrl: null,
            hiddenFormTmpl: mageTemplate(
                '<form target="<%= data.target %>" action="<%= data.action %>" method="POST" ' +
                'hidden enctype="application/x-www-form-urlencoded" class="no-display">' +
                '<% _.each(data.inputs, function(val, key){ %>' +
                '<input value="<%= val %>" name="<%= key %>" type="hidden">' +
                '<% }); %>' +
                '</form>'),
            imports: {
                setCardType: '${ $.provider }:${ $.dataScope }.additional.cc_number'
            }
        },

        /**
         * Before submit action for payment method.
         * @return void
         */
        beforeSubmit: function () {
            var self = this;
            $('body').trigger('processStart');

            this.loadSilentData()
                .done(function (response) {
                    self.postPaymentToGateway(response);
                })
                .fail(function (errors) {
                    $('body').trigger('processStop');
                    self.set('payment_errors', [errors]);
                });
        },

        postPaymentToGateway: function (response) {
            var $iframe = $('#' + this.getCode() + '-transparent-iframe'),
                data = this.preparePaymentData(response),
                tmpl = this.hiddenFormTmpl({
                    data: {
                        target: $iframe.attr('name'),
                        action: this.sopServiceUrl,
                        inputs: data
                    }
                });

            $iframe.on('submit', function (event) {
                event.stopPropagation();
            });

            $(tmpl).appendTo($iframe).submit();
            $iframe.html('');
        },

        preparePaymentData: function (response) {
            var data = response[this.getCode()].fields,
                month;
            data['card_cvn'] = this.source.get(this.dataScope + '.additional.cc_cid');
            month = this.source.get(this.dataScope + '.additional.cc_exp_month');
            month = parseInt(month) < 10 ? '0' + month.toString() : month;
            data['card_expiry_date'] = month + '-' +
                this.source.get(this.dataScope + '.additional.cc_exp_year');
            data['card_number'] = this.source.get(this.dataScope + '.additional.cc_number');
            data['transaction_type'] = 'create_payment_token';
            return data;
        },

        loadSilentData: function () {
            var silentData = $.Deferred(),
                formKey = typeof FORM_KEY !== 'undefined' ? FORM_KEY : $('input[name=form_key]').val(),
                postData,
                profile_id = this.source.get('data.subscription_profile_id'),
                entity_id = this.source.get('data.entity_id');
            postData = {
                'form_key': formKey,
                'cc_type': this.source.get(this.dataScope + '.additional.cc_type')
            };
            if (profile_id) {
                postData.profile_id = profile_id;
            } else if (entity_id) {
                postData.profile_id = entity_id;
            }
            $.ajax({
                url: this.loadSilentDataUrl,
                type: 'post',
                context: this,
                data: postData,
                dataType: 'json',
                success: function (response) {
                    if (response.success && response[this.getCode()]) {
                        silentData.resolve(response);
                        return;
                    }
                    silentData.reject(response);
                }.bind(this)
            });

            return silentData.promise();
        },

        /**
         * Get payment method code
         *
         * @returns {string}
         */
        getCode: function () {
            return this.code;
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
         * Set credit card type based on cc number. Set message if type not supported.
         * @param value
         * @returns {boolean}
         */
        setCardType: function (value) {
            if (value === '' || value === null) return false;
            var result = cardNumberValidator(value);

            if (!result.isPotentiallyValid && !result.isValid) {
                return false;
            }

            if (result.card !== null) {
                if (_.contains(this.availableCardTypes, result.card.type)) {
                    this.source.set(this.dataScope+'.additional.cc_type', result.card.type);
                    this.set('payment_errors', false);
                } else {
                    this.set(
                        'payment_errors',
                        [$t('Provided credit card type (%1) is not available with this payment method.')
                            .replace('%1', result.card.title)]
                    );
                }
            }
        }
    });
});
