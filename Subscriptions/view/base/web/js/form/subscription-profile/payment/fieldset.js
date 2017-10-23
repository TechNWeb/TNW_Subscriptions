/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/components/fieldset',
    'uiRegistry',
    'jquery',
    'mage/template',
    'Magento_Ui/js/lib/spinner',
    'jquery/ui'
], function (Collapsible, registry, $j, template, loader) {
    'use strict';

    return Collapsible.extend({
        defaults: {
            template: 'TNW_Subscriptions/form/subscription-profile/payment/fieldset',
            dataContainer: null,
            payment_errors: null,
            iframeSrc: null,
            checked:false,
            options: [],
            hiddenFormTmpl:
            '<form target="<%= data.target %>" action="<%= data.action %>"' +
            'method="POST" hidden' +
            'enctype="application/x-www-form-urlencoded" class="no-display">' +
            '<% _.each(data.inputs, function(val, key){ %>' +
            '<input value="<%= val %>" name="<%= key %>" type="hidden">' +
            '<% }); %>' +
            '</form>'
        },

        /**
         * Calls initObservable of parent class.
         * Defines observable properties of instance.
         *
         * @returns {Object} Reference to instance
         */
        initObservable: function () {
            this._super()
                .observe('checked payment_errors');

            return this;
        },

        /**
         * Initializes components' configuration.
         *
         * @returns {Fieldset} Chainable.
         */
        initConfig: function () {
            this._super();
            this._wasOpened = this.opened || !this.collapsible;
            this.hiddenFormTmpl = template(this.hiddenFormTmpl);

            return this;
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
                temp[this.options.gateway] = {
                    method: '1'
                };
                postData.payment = temp;

                $j.ajax({
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


        beforeSubmit: function () {
            var postData = {
                'form_key': FORM_KEY,
                'cc_type': this.ccType()
            };

            this.showLoader();
            this.resetErrors();
            $j.ajax({
                url: this.options.orderSaveUrl,
                type: 'post',
                context: this,
                data: postData,
                dataType: 'json',
                success: function (response) {
                    if (response.success && response[this.options.gateway]) {
                        this.postPaymentToGateway(response);
                    } else {
                        this.processErrors(response.error_messages);
                    }
                    this.hideLoader();
                },
                complete: function () {
                    this.hideLoader();
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
         * Processing errors
         */
        processErrors: function (errors) {
            this.set('payment_errors', errors);
        },

        /**
         * Shows form loader.
         */
        hideLoader: function () {
            registry.get('index = ' + this.options.formName).hideLoader();
        },

        /**
         * Hides form loader.
         */
        showLoader: function () {
            registry.get('index = ' + this.options.formName).showLoader();
        },

        /**
         * Resets payment errors.
         */
        resetErrors:function () {
            this.set('payment_errors', '');
        }
    });
});
