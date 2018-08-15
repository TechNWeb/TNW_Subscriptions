/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'uiComponent',
    'TNW_Subscriptions/js/checkout/model/totals'
], function (Component, totals) {
    'use strict';

    return Component.extend({
        isLoading: totals.isLoading
    });
});
