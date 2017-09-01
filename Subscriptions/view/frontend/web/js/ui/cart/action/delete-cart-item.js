/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(
    [
        'jquery',
        'TNW_Subscriptions/js/ui/cart/model/cart',
        'TNW_Subscriptions/js/ui/model/url-builder',
        'mage/storage',
        'TNW_Subscriptions/js/ui/cart/model/full-screen-loader',
        'TNW_Subscriptions/js/ui/model/error-processor'
    ],
    function (
        $,
        cart,
        urlBuilder,
        storage,
        fullScreenLoader,
        errorProcessor
    ) {
        'use strict';

        return function (cartId, itemId) {
            var serviceUrl = urlBuilder.createUrl('/tnwSubscriptions/delete-cart-item', {}),
                payload = {
                    cartId: cartId,
                    itemId: itemId
                };

            fullScreenLoader.startLoader();

            return storage.post(
                serviceUrl, JSON.stringify(payload)
            ).done(
                function (response) {
                    cart.setCartData(response);
                    if (!cart.getItemsCount()) {
                        window.location = window.tnwSubscriptionsCheckoutConfig.cartUrl;
                    } else {
                        fullScreenLoader.stopLoader();
                    }
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response);
                    fullScreenLoader.stopLoader();
                }
            );
        };
    }
);
