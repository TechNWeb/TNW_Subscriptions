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
         * Get shipping rates for specified address.
         * @param {Object} address
         */
        getRates: function (address) {
            var cache, serviceUrl, payload;

            shippingService.isLoading(true);
            cache = rateRegistry.get(address.getCacheKey());

            if (cache) {
                shippingService.setShippingRates(cache);
                shippingService.isLoading(false);
                return;
            }

            serviceUrl = urlBuilder.createUrl('/tnw-subscriptions-carts/mine/estimate-shipping-methods', {});

            payload = JSON.stringify({
                    address: {
                        'street': address.street,
                        'city': address.city,
                        'region_id': address.regionId,
                        'region': address.region,
                        'country_id': address.countryId,
                        'postcode': address.postcode,
                        'email': address.email,
                        'customer_id': address.customerId,
                        'firstname': address.firstname,
                        'lastname': address.lastname,
                        'middlename': address.middlename,
                        'prefix': address.prefix,
                        'suffix': address.suffix,
                        'vat_id': address.vatId,
                        'company': address.company,
                        'telephone': address.telephone,
                        'fax': address.fax,
                        'custom_attributes': address.customAttributes,
                        'save_in_address_book': address.saveInAddressBook
                    }
                }
            );

            storage
                .post(serviceUrl, payload, false)
                .done(function (result) {
                    rateRegistry.set(address.getCacheKey(), result);
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
