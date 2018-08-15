/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'ko',
    'TNW_Subscriptions/js/checkout/model/profile',
    'Magento_Customer/js/customer-data'
], function (ko, profile, customerData) {
    'use strict';

    var cartData = customerData.get('cart'),
        subtotalAmount = parseFloat(cartData().subtotalAmount);

    return {
        totals: profile.totals,
        isLoading: ko.observable(false),

        /**
         * @param {*} code
         * @return {*}
         */
        getSegment: function (code) {
            var i, total;

            if (!this.totals()) {
                return null;
            }

            for (i in this.totals()['total_segments']) { //eslint-disable-line guard-for-in
                total = this.totals()['total_segments'][i];

                if (total.code == code) { //eslint-disable-line eqeqeq
                    return total;
                }
            }

            return {value: 0};
        }
    };
});
