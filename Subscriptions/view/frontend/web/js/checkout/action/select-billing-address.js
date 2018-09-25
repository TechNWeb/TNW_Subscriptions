/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'jquery',
    'TNW_Subscriptions/js/checkout/model/quote'
], function ($, quote) {
    'use strict';

    return function (billingAddress) {
        var address = null;

        if (quote.shippingAddress()
            && billingAddress.getCacheKey() === quote.shippingAddress().getCacheKey()
        ) {
            address = $.extend({}, billingAddress);
            address.saveInAddressBook = null;
        } else {
            address = billingAddress;
        }
        quote.billingAddress(address);
    };
});
