/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'ko',
    'TNW_Subscriptions/js/checkout/model/data-resolver'
], function (ko, dataResolver) {
    'use strict';

    var shippingRates = ko.observableArray([]);

    return {
        isLoading: ko.observable(false),

        /**
         * Set shipping rates
         *
         * @param {*} ratesData
         */
        setShippingRates: function (ratesData) {
            shippingRates(ratesData);
            shippingRates.valueHasMutated();
            dataResolver.resolveShippingRates(ratesData);
        },

        /**
         * Get shipping rates
         *
         * @returns {*}
         */
        getShippingRates: function () {
            return shippingRates;
        }
    };
});
