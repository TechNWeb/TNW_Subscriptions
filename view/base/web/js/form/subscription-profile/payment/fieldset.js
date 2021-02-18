/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'TNW_Subscriptions/js/form/subscription-profile/payment/base',
    'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
    'mage/translate',
    'uiRegistry',
    'jquery',
    'mage/template',
    'Magento_Ui/js/lib/spinner',
    'jquery/ui'
], function (PaymentBase, cardNumberValidator, $t, registry, $j, template) {
    'use strict';

    return PaymentBase.extend({
        defaults: {
            template: 'TNW_Subscriptions/form/subscription-profile/payment/fieldset',
            dataContainer: null,
            iframeSrc: null,
            availableCardTypes: null,
            options: [],
            hiddenFormTmpl:
            '<form target="<%= data.target %>" action="<%= data.action %>"' +
            'method="POST" hidden' +
            'enctype="application/x-www-form-urlencoded" class="no-display">' +
            '<% _.each(data.inputs, function(val, key){ %>' +
            '<input value="<%= val %>" name="<%= key %>" type="hidden">' +
            '<% }); %>' +
            '</form>',
            imports: {
                setCardType: '${ $.provider }:${ $.dataScope }.additional.cc_number'
            }
        },

        /**
         * Initializes components' configuration.
         *
         * @returns {Fieldset} Chainable.
         */
        initConfig: function () {
            this._super();
            this.hiddenFormTmpl = template(this.hiddenFormTmpl);

            return this;
        },

        /**
         * Before submit action for payment method.
         */
        beforeSubmit: function () {
            var postData = (typeof FORM_KEY !== 'undefined') ? {'form_key': FORM_KEY} : {},
                self = this;
            postData['cc_type'] =  this.ccType();
            $j('body').trigger('processStart');
            this.resetErrors();
            $j.ajax({
                url: this.options.orderSaveUrl,
                type: 'post',
                context: this,
                data: postData,
                dataType: 'json',
                success: function (response) {
                    if (response.success && response[self.options.gateway]) {
                        this.postPaymentToGateway(response);
                    } else {
                        this.processErrors(response.error_messages);
                    }
                }
            });
        },

        /**
         * Post data to gateway for credit card validation.
         *
         * @param {Object} response
         * @private
         */
        postPaymentToGateway: function (response) {
            var $iframeSelector =  $j('[data-container="' + this.options.gateway + '-transparent-iframe"]'),
                data,
                tmpl,
                iframe;

            data = this.preparePaymentData(response);
            tmpl = this.hiddenFormTmpl({
                data: {
                    target: $iframeSelector.attr('name'),
                    action: this.options.cgiUrl,
                    inputs: data
                }
            });

            iframe = $iframeSelector
                .on('submit', function (event) {
                    event.stopPropagation();
                });
            $j(tmpl).appendTo(iframe).submit();
            iframe.html('');
        },

        /**
         * @returns {String}
         */
        ccType: function () {
            return $j(
                '[data-container="' + this.options.gateway + '-cc-type"]'
            ).val();
        },

        /**
         * Add credit card fields to post data for gateway.
         */
        preparePaymentData: function (response) {
            var ccfields,
                data,
                preparedata;

            data = response[this.options.gateway].fields;
            ccfields =  $j.parseJSON(this.options.cardFieldsMap);

            if ( $j('[data-container="' + this.options.gateway + '-cc-cvv"]').length) {
                data[ccfields.cccvv] =  $j(
                    '[data-container="' + this.options.gateway + '-cc-cvv"]'
                ).val();
            }
            preparedata = this.prepareExpDate();
            data[ccfields.ccexpdate] = preparedata.month + this.options.dateDelim + preparedata.year;
            data[ccfields.ccnum] =  $j(
                '[data-container="' + this.options.gateway + '-cc-number"]'
            ).val();

            return data;
        },

        /**
         * Grab Month and Year into one
         */
        prepareExpDate: function () {
            var year =  $j('[data-container="' + this.options.gateway + '-cc-year"]').val(),
                month = parseInt(
                    $j('[data-container="' + this.options.gateway + '-cc-month"]').val(), 10
                );

            if (year.length > this.options.expireYearLength) {
                year = year.substring(year.length - this.options.expireYearLength);
            }

            if (month < 10) {
                month = '0' + month;
            }

            return {
                month: month, year: year
            };
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
