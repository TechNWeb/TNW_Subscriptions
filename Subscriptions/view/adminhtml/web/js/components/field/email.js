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

        openMod: function (firstName, lastName) {
            var modal = uiRegistry.get('index = customer_account_already_exists');

            // make message for label.
            var text = $j.mage.__('Subscription should be linked to the existing account for ');
            text = text + firstName + ' ' + lastName;

            if (modal && typeof modal._elems[0] != "undefined") {
                modal._elems[0].openModal();

                //change label due to first name and last name of customer changed.
                var option = $j('.admin__field.admin__field-option :input[value="link"]');

                if (option) {
                    var label = option.parent().find('.admin__field-label');

                    if (label) {
                        label.text(text);
                    }
                }
            }
        },

        sendCheckEmailAjax: function (url) {
            var self = this;

            $j.ajax({
                showLoader: true,
                url: url,
                data: {email: this.value},
                type: "POST",
                dataType: 'json'
            }).done(function (data) {
               // if (data.exist) {
                    self.openMod(data.firstname, data.lastname);
                //}
            });
        }
    });
});
