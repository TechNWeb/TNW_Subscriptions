/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

/**
 * Change discount amount symbol according to discount type (price or percent)
 */

define([
    'jquery'
], function ($) {
    'use strict';

    return {
        /**
         * Constructor component
         */
        'TNW_Subscriptions/js/system/config/currency_percent': function () {
            var discountType = $('#tnw_subscriptions_product_discount_discount_type'),
                discountAmountSymbol = $('label.admin__addon-prefix[for="tnw_subscriptions_product_discount_discount_amount"] span');
            var currencySymbol = discountAmountSymbol.text();

            discountType.change(function () {
                var value = parseInt(discountType.val());
                var symbol = '';

                switch (value) {
                    case 1: // currency
                        symbol = currencySymbol;
                        break;
                    case 2: // percent
                        symbol = '%';
                        break;
                }

                discountAmountSymbol.text(symbol);
            });

            discountType.change(); // Initial setup
        }
    }
});
