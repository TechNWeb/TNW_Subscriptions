/**
 * Copyright © 2017 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define(
    [
        'jquery',
        'ko',
        'uiComponent',
        'TNW_Subscriptions/js/ui/cart/model/cart',
        'TNW_Subscriptions/js/ui/cart/action/update-cart-item',
        'TNW_Subscriptions/js/ui/cart/action/delete-cart-item',
        'Magento_Catalog/js/price-utils'
    ],
    function(
        $,
        ko,
        Component,
        cart,
        updateCartItemAction,
        deleteCartItemAction,
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
            },

            /**
             * On key up in qty input event handler
             *
             * @param {Object} item
             * @param {Event} event
             */
            onQtyKeyUp: function (item, event) {
                var input = $(event.currentTarget),
                    value = input.val(),
                    origValue = input.data('orig-qty'),
                    isNumber = /^\d+(\.\d{0,})?/.test(value),
                    updateButton = input.next('[data-role=update-button][data-item-id=' + item.item_id + ']'),
                    isUpdateEnable = isNumber && value != origValue && value != 0;

                if (isUpdateEnable) {
                    updateButton.show();
                } else {
                    updateButton.hide();
                }
            },

            /**
             * On delete link click event handler
             *
             * @param {Object} item
             */
            onDeleteClick: function (item) {
                deleteCartItemAction(cart.getCartId(), item.item_id);
            },

            /**
             * On update button click event handler
             *
             * @param {Object} item
             */
            onUpdateButtonClick: function (item) {
                updateCartItemAction(item);
            }
        });
    }
);
