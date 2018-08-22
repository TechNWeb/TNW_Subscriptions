/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'mage/storage',
    'TNW_Subscriptions/js/checkout/model/shipping-service',
    'TNW_Subscriptions/js/checkout/model/shipping/rate/registry',
    'TNW_Subscriptions/js/checkout/model/error-processor'
], function (storage, shippingService, rateRegistry, errorProcessor) {
    'use strict';

    return {
        /**
         * @param {Object} address
         */
        getRates: function (address) {
            var cache;

            shippingService.isLoading(true);
            cache = rateRegistry.get(address.getKey());

            if (cache) {
                shippingService.setShippingRates(cache);
                shippingService.isLoading(false);
            } else {
                storage.post(
                    'tnw_subscriptions/cart/estimateshippingmethods',
                    JSON.stringify({
                        addressId: address.customerAddressId
                    }),
                    false
                ).done(function (result) {
                    rateRegistry.set(address.getKey(), result);
                    shippingService.setShippingRates(result);
                }).fail(function (response) {
                    shippingService.setShippingRates([]);
                    errorProcessor.process(response);
                }).always(function () {
                    shippingService.isLoading(false);
                });
            }
        }
    };
});
