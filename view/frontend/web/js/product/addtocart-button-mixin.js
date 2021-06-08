/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([], function () {
    return function (AddtocartButton) {
        return AddtocartButton.extend({
            isSubscribe: function (row) {
                return row['extension_attributes']['is_subscribe'];
            },

            isOneTimePurchase: function (row) {
                return row['extension_attributes']['is_one_time_purchase'];
            },

            isSubscribeOnly: function (row) {
                return row['extension_attributes']['is_subscribe_only'];
            },

            getSubsAddToCartParams: function (row) {
                return row['extension_attributes']['subs_addtocart_params'];
            },

            getHiddenButtonClass: function (row) {
                return 'product-addtocart-button-hidden subscription-dropdown-hidden-' + row['id'];
            }
        })
    }
})
