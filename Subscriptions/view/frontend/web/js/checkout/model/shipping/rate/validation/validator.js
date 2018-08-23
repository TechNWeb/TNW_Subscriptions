/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
    'ko',
    'mage/translate',
    'uiRegistry',
    'TNW_Subscriptions/js/checkout/model/shipping/rate/validation/rules',
    'TNW_Subscriptions/js/checkout/model/shipping/address/form-popup-state',
    'TNW_Subscriptions/js/checkout/model/address-converter',
    'TNW_Subscriptions/js/checkout/model/checkout-quotes'
], function (
    $,
    ko,
    $t,
    uiRegistry,
    rateValidationRules,
    formPopUpState,
    addressConverter,
    checkoutQuotes
) {
    'use strict';

    return {
        validateAddressTimeout: 0,
        validateDelay: 2000,

        /**
         * Perform postponed binding for fieldset elements
         *
         * @param {String} formPath
         */
        initFields: function (formPath) {
            var self = this;

            $.each(rateValidationRules.getObservableFields(), function (index, field) {
                uiRegistry.async(formPath + '.' + field)(self.bindHandler.bind(self));
            });
        },

        /**
         * @param {Object} element
         * @param {Number} delay
         */
        bindHandler: function (element, delay) {
            var self = this;

            delay = typeof delay === 'undefined' ? self.validateDelay : delay;

            if (element.component.indexOf('/group') !== -1) {
                $.each(element.elems(), function (index, elem) {
                    self.bindHandler(elem, delay);
                });
            } else {
                element.on('value', function () {
                    if (!formPopUpState.isVisible()) {
                        clearTimeout(self.validateAddressTimeout);
                        self.validateAddressTimeout = setTimeout(self.validateFields.bind(self), delay);
                    }
                });
            }
        },

        /**
         * Convert form data to quote address and validate fields for shipping rates
         */
        validateFields: function () {
            var addressFlat = uiRegistry.get('checkoutProvider').shippingAddress;
            if (rateValidationRules.validateAddressData(addressFlat)) {
                checkoutQuotes.shippingAddress(
                    addressConverter.formAddressDataToQuoteAddress(addressFlat)
                );
            }
        }
    };
});
