/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/form/components/fieldset',
    'mage/template',
    'Magento_Ui/js/lib/spinner',
    'jquery/ui'
], function ($, $t, fieldset, template) {
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
            selector: 'co-transparent-form-braintree',
            hiddenFormTmpl:
            '<form id="<%= data.id %>" target="<%= data.target %>" action="#" method="POST">' +
                '<div id="card-number"></div>' +
                '<div id="expiration-month"></div>' +
                '<div id="expiration-year"></div>' +
                '<div id="cvv"></div>' +
            '</form>'
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
            if (!this.clientToken) {
                this.processErrors($.mage.__('This payment is not available'));

                return;
            }

            if (!this.scriptLoaded()) {
                this.loadScript();
            }
        },

        /**
         * Load external Braintree SDK
         */
        loadScript: function () {
            var self = this,
                state = self.scriptLoaded;

            $('body').trigger('processStart');
            require([this.sdkUrl], function (braintree) {
                state(true);
                self.braintree = braintree;
                self.initBraintree();
                $('body').trigger('processStop');
            });
        },

        /**
         * Setup Braintree SDK
         */
        initBraintree: function () {
            try {
                $('body').trigger('processStart');

                var $iframeSelector =  $('[data-container="' + this.code + '-transparent-iframe"]');
                var tmpl = this.hiddenFormTmpl({
                    data: {
                        target: $iframeSelector.attr('name'),
                        id: this.selector
                    }
                });

                var form = $(tmpl);
                form.appendTo($iframeSelector);
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
                        this.processErrors(response.message);
                    }
                });

                //form.submit();
                //$iframeSelector.html('');
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
            var fields = {
                    number: {
                        selector: '#card-number'
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
                    selector: '#cvv'
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
        }
    });
});