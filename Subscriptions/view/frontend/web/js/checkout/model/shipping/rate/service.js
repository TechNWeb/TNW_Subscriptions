/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'TNW_Subscriptions/js/checkout/model/profile',
    'TNW_Subscriptions/js/checkout/model/shipping/rate/processor/new-address',
    'TNW_Subscriptions/js/checkout/model/shipping/rate/processor/customer-address'
], function (profile, defaultProcessor, customerAddressProcessor) {
    'use strict';

    var processors = [];

    processors.default =  defaultProcessor;
    processors['customer-address'] = customerAddressProcessor;

    profile.shippingAddress.subscribe(function () {
        var type = profile.shippingAddress().getType();

        if (processors[type]) {
            processors[type].getRates(profile.shippingAddress());
        } else {
            processors.default.getRates(profile.shippingAddress());
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
