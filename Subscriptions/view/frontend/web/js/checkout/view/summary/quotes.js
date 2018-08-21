/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

define([
    'ko',
    'uiComponent',
    'TNW_Subscriptions/js/checkout/model/checkout-quotes'
], function (ko, Component, checkoutQuotes) {
    'use strict';

    return Component.extend({
        quotes: ko.observable([]),

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();
            // Set initial items to observable field
            this.quotes(checkoutQuotes.getQuotes());
        }
    });
});
