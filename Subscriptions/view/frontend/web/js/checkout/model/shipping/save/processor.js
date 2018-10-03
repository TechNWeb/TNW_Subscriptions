/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'ko',
    'Magento_Checkout/js/model/quote',
    'mage/storage',
    'TNW_Subscriptions/js/checkout/model/payment/service',
    'Magento_Checkout/js/model/payment/method-converter',
    'Magento_Checkout/js/model/error-processor',
    'TNW_Subscriptions/js/checkout/model/resource-url-manager'
], function (
    ko,
    quote,
    storage,
    paymentService,
    methodConverter,
    errorProcessor,
    resourceUrlManager
) {
    'use strict';

    return {
        /**
         * @return {jQuery.Deferred}
         */
        saveShippingInformation: function () {
            var payload, serviceUrl;

            paymentService.isLoading(true);

            serviceUrl = resourceUrlManager.getUrlForSetShippingInformation(quote);

            payload = JSON.stringify({
                addressInformation: {
                    'shipping_address': quote.shippingAddress(),
                    'billing_address': quote.billingAddress(),
                    'shipping_method_code': quote.shippingMethod()['method_code'],
                    'shipping_carrier_code': quote.shippingMethod()['carrier_code']
                }
            });

            return storage
                .post(serviceUrl, payload, false)
                .done(function (response) {
                    quote.setTotals(response.totals);
                    paymentService.setPaymentMethods(methodConverter(response['payment_methods']));
                })
                .fail(function (response) {
                    errorProcessor.process(response);
                })
                .always(function () {
                    paymentService.isLoading(false);
                });
        }
    };
});
