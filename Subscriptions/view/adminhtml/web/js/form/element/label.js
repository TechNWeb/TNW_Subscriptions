/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'mage/translate'
], function (Abstract, $j) {
    'use strict';

    return Abstract.extend({
        defaults: {
            text: ''
        },

        /**
         * {@inheritdoc}
         */
        setInitialValue: function () {
            this._super();

            var firstPart = $j.mage.__('We found a customer account for <b>');
            var secondPart = $j.mage.__('</b> and email address <b>');
            var thirdPart = $j.mage.__('</b>. What would you like to do?');

            var name = typeof this.imports.customerName == "undefined" ? '' : this.imports.customerName;
            var email= typeof this.imports.customerEmail == "undefined" ? '' : this.imports.customerEmail;

            this.text = firstPart + name + secondPart + email + thirdPart;

            return this;
        }
    });
});
