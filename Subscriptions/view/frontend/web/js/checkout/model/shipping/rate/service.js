/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'TNW_Subscriptions/js/checkout/model/checkout-quotes',
    'TNW_Subscriptions/js/checkout/model/shipping/rate/processor/new-address',
    'TNW_Subscriptions/js/checkout/model/shipping/rate/processor/customer-address'
], function (checkoutQuotes, defaultProcessor, customerAddressProcessor) {
    'use strict';

    var processors = [];

    processors.default =  defaultProcessor;
    processors['customer-address'] = customerAddressProcessor;

    checkoutQuotes.shippingAddress.subscribe(function () {
        var type = checkoutQuotes.shippingAddress().getType();

        if (processors[type]) {
            processors[type].getRates(checkoutQuotes.shippingAddress());
        } else {
            processors.default.getRates(checkoutQuotes.shippingAddress());
        }
    });

    return {
        /**
         * @param {String} type
         * @param {*} processor
         */
        registerProcessor: function (type, processor) {
            processors[type] = processor;
        }
    };
});
