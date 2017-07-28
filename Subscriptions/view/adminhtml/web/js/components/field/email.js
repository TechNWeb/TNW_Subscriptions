/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'uiRegistry',
    'mage/translate'
], function (Abstract, $j, uiRegistry) {
    'use strict';

    return Abstract.extend({
        default: {
            valueBefore: '',
            options: []
        },

        /**
         * {@inheritDoc}
         */
        hasChanged: function () {
            if (this.valueBefore != this.value()) {

                var url = typeof this.imports.checkEmailUrl == "undefined"
                    ? '' : this.imports.checkEmailUrl;

                var validate = this.validate();

                if (validate.valid && this.value()) {
                    this.sendCheckEmailAjax(url);
                }
            }

            this.valueBefore = this.value();
        },

        /**
         * Open modal window.
         *
         * @param customerName
         * @param customerEmail
         */
        openMod: function (customerName, customerEmail) {
            var modal = uiRegistry.get('index = customer_account_already_exists');

            if (modal && typeof modal._elems[0] != "undefined") {
                modal._elems[0].openModal();

                this.updateRadio(customerName);
                this.updateQuestion(customerName, customerEmail)
            }
        },

        /**
         * Send ajax to check if customer with such email exists.
         *
         * @param url
         */
        sendCheckEmailAjax: function (url) {
            var self = this;

            $j.ajax({
                showLoader: true,
                url: url,
                data: {email: this.value},
                type: "POST",
                dataType: 'json'
            }).done(function (data) {
                if (data.exist) {
                    self.openMod(data.customerName, data.customerEmail);
                }
            });
        },

        /**
         * Update question with new email and customer name.
         *
         * @param customerName
         * @param customerEmail
         */
        updateQuestion: function (customerName, customerEmail) {
            var firstPart = $j.mage.__('We found a customer account for ');
            var secondPart = $j.mage.__(' and email address ');
            var thirdPart = $j.mage.__('. What would you like to do?');

            var text = firstPart + customerName + secondPart + customerEmail + thirdPart;

            $j('.admin__fieldset #customer-exists-label').text(text);
        },

        /**
         * Update radio with new customer name.
         *
         * @param customerName
         */
        updateRadio: function (customerName) {
            // make message for label.
            var text = $j.mage.__('Subscription should be linked to the existing account for ') + customerName;

            //change label due to first name and last name of customer changed.
            var option = $j('.admin__field.admin__field-option :input[value="link"]');

            if (option) {
                var label = option.parent().find('.admin__field-label');

                if (label) {
                    label.text(text);
                }
            }
        }
    });
});
