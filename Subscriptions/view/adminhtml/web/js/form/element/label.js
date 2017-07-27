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

            var firstPart = $j.mage.__('We found a customer account for ');
            var secondPart = $j.mage.__(' and email address ');
            var thirdPart = $j.mage.__('. What would you like to do?');

            var name = typeof this.customerName == "undefined" ? '' : this.customerName;
            var email= typeof this.customerEmail == "undefined" ? '' : this.customerEmail;

            this.text = firstPart + name + secondPart + email + thirdPart;

            return this;
        }
    });
});
