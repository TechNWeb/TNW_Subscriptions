/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'TNW_Subscriptions/js/checkout/model/quote'
], function (quote) {
    'use strict';

    return function (paymentMethod) {
        quote.paymentMethod(paymentMethod);
    };
});
