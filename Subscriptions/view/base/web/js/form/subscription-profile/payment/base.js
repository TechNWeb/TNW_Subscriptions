/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/components/fieldset',
    'uiRegistry',
    'jquery',
    'Magento_Ui/js/lib/spinner',
    'jquery/ui'
], function (Collapsible, registry, $j) {
    'use strict';

    return Collapsible.extend({
        defaults: {
            template: 'TNW_Subscriptions/form/subscription-profile/payment/fieldset',
            dataContainer: null,
            payment_errors: null,
            iframeSrc: null,
            checked:false,
            options: [],
            hiddenFormTmpl: ''
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

        /**
         * Before submit action for payment method.
         */
        beforeSubmit: function () {
            registry.get('index = '+this.options.formName).triggerSave([]);
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
