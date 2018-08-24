/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
    'TNW_Subscriptions/js/checkout/model/checkout-quotes'
], function ($, checkoutQuotes) {
    'use strict';

    return function (billingAddress) {
        var address = null;

        if (checkoutQuotes.shippingAddress()
            && billingAddress.getCacheKey() == checkoutQuotes.shippingAddress().getCacheKey()
        ) {
            address = $.extend({}, billingAddress);
            address.saveInAddressBook = null;
        } else {
            address = billingAddress;
        }
        checkoutQuotes.billingAddress(address);
    };
});
