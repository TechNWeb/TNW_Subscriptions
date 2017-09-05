/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(
    [
        'jquery',
        'uiComponent',
        'TNW_Subscriptions/js/ui/cart/model/cart',
        'Magento_Catalog/js/price-utils'
    ],
    function(
        $,
        Component,
        cart,
        priceUtils
    ) {
        'use strict';

        return Component.extend({
            items: cart.cartItems,
            isPlanSelected: cart.isPlanSelected,

            /**
             * Format price amount
             *
             * @param {Number} price
             * @returns {String}
             */
            formatPrice: function (price) {
                return priceUtils.formatPrice(price, cart.getPriceFormat());
            }
        });
    }
);
