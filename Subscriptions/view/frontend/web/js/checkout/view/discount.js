/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'ko',
    'uiComponent',
    'TNW_Subscriptions/js/checkout/model/profile'
], function (ko, Component, profile) {
    'use strict';

    var totals = profile.getTotals(),
        couponCode = ko.observable(null),
        isApplied;

    if (totals()) {
        couponCode(totals()['coupon_code']);
    }

    isApplied = ko.observable(couponCode() != null);

    return Component.extend({
        /**
         * Coupon Code
         */
        couponCode: couponCode,

        /**
         * Applied flag
         */
        isApplied: isApplied,

        /**
         * Coupon code application procedure
         */
        apply: function () {
            if (this.validate()) {
                //TODO
            }
        },

        /**
         * Cancel using coupon
         */
        cancel: function () {
            if (this.validate()) {
                couponCode('');
                //TODO
            }
        },

        /**
         * Coupon form validation
         *
         * @returns {Boolean}
         */
        validate: function () {
            var form = '#discount-form';

            return $(form).validation() && $(form).validation('isValid');
        },

        /**
         * @return {*}
         */
        getFormKey: function () {
            return window.checkoutConfig.formKey;
        }
    });
});
