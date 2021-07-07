/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'mage/storage',
    'Magento_Checkout/js/model/url-builder'
], function (storage, urlBuilder) {
    'use strict';

    return function (deferred, email, cartId) {
        return storage.post(
            urlBuilder.createUrl('/tnw-subscriptions-customer/isCustomerExistsAndShouldBeLoggedIn', {}),
            JSON.stringify({
                customerEmail: email,
                cartId: cartId
            }),
            false
        ).done(function (status) {
            deferred.resolve(status);
        }).fail(function () {
            deferred.reject();
        });
    };
});
