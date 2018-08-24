/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'TNW_Subscriptions/js/checkout/model/checkout-quotes'
], function (checkoutQuotes) {
    'use strict';

    return function (shippingAddress) {
        checkoutQuotes.shippingAddress(shippingAddress);
    };
});
