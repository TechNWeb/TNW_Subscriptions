/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
define([
    'jquery',
    'TNW_Subscriptions/js/checkout/view/checkout-force-login-status',
], function ($, checkoutForceLoginStatus) {
    'use strict';
    return function (originalShipping) {
        return originalShipping.extend({
            defaults: {
                template: 'TNW_Subscriptions/checkout/shipping',
                forceLoginStatus: checkoutForceLoginStatus
            },
        });
    };
});
