/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'mage/storage',
    'TNW_Subscriptions/js/checkout/model/quote',
    'TNW_Subscriptions/js/checkout/model/shipping/service',
    'TNW_Subscriptions/js/checkout/model/shipping/rate/registry',
    'TNW_Subscriptions/js/checkout/model/error-processor',
    'Magento_Checkout/js/model/url-builder'
], function (storage, quote, shippingService, rateRegistry, errorProcessor, urlBuilder) {
    'use strict';

    return {
        /**
         * @param {Object} address
         */
        getRates: function (address) {
            var cache, serviceUrl, payload;

            shippingService.isLoading(true);
            cache = rateRegistry.get(address.getKey());

            if (cache) {
                shippingService.setShippingRates(cache);
                shippingService.isLoading(false);
                return;
            }

            serviceUrl = urlBuilder.createUrl('/tnw-subscriptions-carts/mine/estimate-shipping-methods-by-address-id', {});

            payload = JSON.stringify({
                addressId: address.customerAddressId
            });

            storage
                .post(serviceUrl, payload, false)
                .done(function (result) {
                    rateRegistry.set(address.getKey(), result);
                    shippingService.setShippingRates(result);
                })
                .fail(function (response) {
                    shippingService.setShippingRates([]);
                    errorProcessor.process(response);
                })
                .always(function () {
                    shippingService.isLoading(false);
                });
        }
    };
});
